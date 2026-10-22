<?php

declare(strict_types=1);

/**
 * @author Paweł Majcherczyk <pawel.majcherczyk@lizardmedia.pl>
 * @copyright Copyright (C) 2026 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Test\Unit\Model\QueueHandler;

use LizardMedia\VarnishWarmer\Api\Config\GeneralConfigProviderInterface;
use LizardMedia\VarnishWarmer\Api\ProgressHandler\QueueProgressLoggerInterface;
use LizardMedia\VarnishWarmer\Model\Adapter\Guzzle\ClientFactory;
use LizardMedia\VarnishWarmer\Model\HttpContext\VaryCookieProvider;
use LizardMedia\VarnishWarmer\Model\QueueHandler\VarnishUrlRegenerator;
use LizardMedia\VarnishWarmer\Test\Unit\Instantiator;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class VarnishUrlRegeneratorTest extends TestCase
{
    private ?RecordingServer $server = null;
    private TestHandler $logRecords;
    private array $varyCookiesByStoreId = [];
    private string $httpAuthUsername = '';

    protected function tearDown(): void
    {
        $this->server?->stop();
    }

    public function testSendsPlainGetRequestForEveryUrl(): void
    {
        $this->server = RecordingServer::start();
        $regenerator = $this->createRegenerator(4);

        $regenerator->addUrlToRegenerate($this->server->url(''));
        $regenerator->addUrlToRegenerate($this->server->url('women.html'));
        $regenerator->regenerate();

        $requests = $this->server->requests();
        self::assertEqualsCanonicalizing(['/', '/women.html'], array_column($requests, 'target'));
        foreach ($requests as $request) {
            self::assertSame('GET', $request['method']);
            self::assertArrayNotHasKey('x-magento-tags-pattern', $request['headers']);
            self::assertArrayNotHasKey('cookie', $request['headers']);
            self::assertSame($this->server->hostHeader(), $request['headers']['host'] ?? null);
        }
    }

    public function testWarmsEveryCacheVariantGuestsOfTheStoreViewGet(): void
    {
        $this->varyCookiesByStoreId = [2 => [null, 'en-store-hash']];
        $this->server = RecordingServer::start();
        $regenerator = $this->createRegenerator(4);

        $regenerator->addUrlToRegenerate($this->server->url('women.html'), 2);
        $regenerator->regenerate();

        $requests = $this->server->requests();
        self::assertSame(['/women.html', '/women.html'], array_column($requests, 'target'));
        self::assertEqualsCanonicalizing(
            [null, 'X-Magento-Vary=en-store-hash'],
            array_map(static fn (array $request): ?string => $request['headers']['cookie'] ?? null, $requests)
        );
    }

    public function testLogsEveryRegeneratedUrl(): void
    {
        $this->server = RecordingServer::start();
        $regenerator = $this->createRegenerator(4);
        $urls = [$this->server->url('women.html'), $this->server->url('men.html')];

        foreach ($urls as $url) {
            $regenerator->addUrlToRegenerate($url);
        }
        $regenerator->regenerate();

        self::assertEqualsCanonicalizing($urls, $this->loggedUrls());
    }

    public function testLogsResponseCodeOfEveryRegeneratedUrl(): void
    {
        $this->server = RecordingServer::start();
        $regenerator = $this->createRegenerator(4);

        $regenerator->addUrlToRegenerate($this->server->url('women.html'));
        $regenerator->regenerate();

        $codes = array_map(
            static fn ($record) => $record['context']['code'] ?? null,
            array_filter(
                $this->logRecords->getRecords(),
                static fn ($record): bool => isset($record['context']['url'])
            )
        );
        self::assertSame([200], array_values($codes));
    }

    public function testAuthenticatesWhenHttpAuthenticationIsConfigured(): void
    {
        $this->httpAuthUsername = 'stage';
        $this->server = RecordingServer::start();
        $regenerator = $this->createRegenerator(4);

        $regenerator->addUrlToRegenerate($this->server->url('women.html'));
        $regenerator->regenerate();

        self::assertSame(
            'Basic ' . base64_encode('stage:secret'),
            $this->server->requests()[0]['headers']['authorization'] ?? null
        );
    }

    public function testKeepsNumberOfParallelRequestsWithinConfiguredLimit(): void
    {
        $this->server = RecordingServer::start(300);
        $regenerator = $this->createRegenerator(3);

        for ($page = 1; $page <= 10; $page++) {
            $regenerator->addUrlToRegenerate($this->server->url('page-' . $page));
        }
        $regenerator->regenerate();

        $requests = $this->server->requests();
        $maxInFlight = max(array_column($requests, 'in_flight'));
        self::assertCount(10, $requests);
        self::assertLessThanOrEqual(3, $maxInFlight);
        self::assertGreaterThan(1, $maxInFlight);
    }

    public function testLogsConnectionFailureWithoutInterruptingQueue(): void
    {
        $this->server = RecordingServer::start();
        $unreachableUrl = RecordingServer::closedPortUrl('women.html');
        $regenerator = $this->createRegenerator(4);

        $regenerator->addUrlToRegenerate($unreachableUrl);
        $regenerator->addUrlToRegenerate($this->server->url('men.html'));
        $regenerator->regenerate();

        self::assertTrue($this->hasErrorAbout($unreachableUrl));
        self::assertSame(['/men.html'], array_column($this->server->requests(), 'target'));
    }

    public function testGivesUpOnResponseThatTakesLongerThanTimeout(): void
    {
        $this->server = RecordingServer::start(3000);
        $regenerator = $this->createRegenerator(4, 0.5);
        $slowUrl = $this->server->url('slow.html');

        $startedAt = microtime(true);
        $regenerator->addUrlToRegenerate($slowUrl);
        $regenerator->regenerate();

        self::assertLessThan(2.5, microtime(true) - $startedAt);
        self::assertTrue($this->hasErrorAbout($slowUrl));
    }

    private function createRegenerator(int $maxParallelRequests, float $timeoutSeconds = 60.0): VarnishUrlRegenerator
    {
        $generalConfig = $this->createStub(GeneralConfigProviderInterface::class);
        $generalConfig->method('getMaxConcurrentRegenerationProcesses')->willReturn($maxParallelRequests);
        $generalConfig->method('getHttpAuthUsername')->willReturnCallback(fn (): string => $this->httpAuthUsername);
        $generalConfig->method('getHttpAuthPassword')->willReturn('secret');

        $varyCookieProvider = $this->createStub(VaryCookieProvider::class);
        $varyCookieProvider->method('getVaryCookies')->willReturnCallback(
            fn (int $storeId): array => $this->varyCookiesByStoreId[$storeId] ?? [null]
        );

        $this->logRecords = new TestHandler();

        return Instantiator::create(VarnishUrlRegenerator::class, [
            GeneralConfigProviderInterface::class => $generalConfig,
            LoggerInterface::class => new Logger('varnish-warmer-test', [$this->logRecords]),
            QueueProgressLoggerInterface::class => $this->createStub(QueueProgressLoggerInterface::class),
            ClientFactory::class => new ClientFactory($generalConfig, 1.0, $timeoutSeconds),
            VaryCookieProvider::class => $varyCookieProvider,
        ]);
    }

    private function loggedUrls(): array
    {
        return array_values(array_filter(array_map(
            static fn ($record) => $record['context']['url'] ?? null,
            $this->logRecords->getRecords()
        )));
    }

    private function hasErrorAbout(string $url): bool
    {
        foreach ($this->logRecords->getRecords() as $record) {
            if ($record['level'] >= 400 && in_array($url, $record['context'], true)) {
                return true;
            }
        }

        return false;
    }
}
