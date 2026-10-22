<?php

declare(strict_types=1);

/**
 * @author Paweł Majcherczyk <pawel.majcherczyk@lizardmedia.pl>
 * @copyright Copyright (C) 2026 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Test\Unit\Model\QueueHandler;

use LizardMedia\VarnishWarmer\Api\Config\GeneralConfigProviderInterface;
use LizardMedia\VarnishWarmer\Api\Config\PurgingConfigProviderInterface;
use LizardMedia\VarnishWarmer\Api\ProgressHandler\QueueProgressLoggerInterface;
use LizardMedia\VarnishWarmer\Model\Adapter\ReactPHP\ClientFactory as ReactClientFactory;
use LizardMedia\VarnishWarmer\Model\QueueHandler\VarnishUrlPurger;
use LizardMedia\VarnishWarmer\Test\Unit\Instantiator;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use React\EventLoop\Factory as ReactLoopFactory;

final class VarnishUrlPurgerTest extends TestCase
{
    private ?RecordingServer $server = null;
    private TestHandler $logRecords;
    private bool $customPurgeHostEnabled = false;
    private string $hostForHeader = '';

    protected function tearDown(): void
    {
        $this->server?->stop();
    }

    public function testSendsPurgeRequestWithAllTagsPatternForEveryUrl(): void
    {
        $this->server = RecordingServer::start();
        $purger = $this->createPurger(4);

        $purger->addUrlToPurge($this->server->url('women.html'));
        $purger->addUrlToPurge($this->server->url('men.html'));
        $purger->purge();

        $requests = $this->server->requests();
        self::assertEqualsCanonicalizing(['/women.html', '/men.html'], array_column($requests, 'target'));
        foreach ($requests as $request) {
            self::assertSame('PURGE', $request['method']);
            self::assertSame('.*', $request['headers']['x-magento-tags-pattern'] ?? null);
            self::assertSame($this->server->hostHeader(), $request['headers']['host'] ?? null);
        }
    }

    public function testLogsResponseCodeOfEveryPurgedUrl(): void
    {
        $this->server = RecordingServer::start();
        $purger = $this->createPurger(4);
        $urls = [$this->server->url('women.html'), $this->server->url('men.html')];

        foreach ($urls as $url) {
            $purger->addUrlToPurge($url);
        }
        $purger->purge();

        foreach ($urls as $url) {
            self::assertSame(200, $this->loggedResponseCode($url));
        }
    }

    public function testSendsConfiguredHostHeaderWhenCustomPurgeHostIsEnabled(): void
    {
        $this->customPurgeHostEnabled = true;
        $this->hostForHeader = 'shop.test';
        $this->server = RecordingServer::start();
        $purger = $this->createPurger(4);

        $purger->addUrlToPurge($this->server->url('women.html'));
        $purger->purge();

        self::assertSame('shop.test', $this->server->requests()[0]['headers']['host'] ?? null);
    }

    public function testKeepsNumberOfParallelRequestsWithinConfiguredLimit(): void
    {
        $this->server = RecordingServer::start(300);
        $purger = $this->createPurger(3);

        for ($page = 1; $page <= 10; $page++) {
            $purger->addUrlToPurge($this->server->url('page-' . $page));
        }
        $purger->purge();

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
        $purger = $this->createPurger(4);

        $purger->addUrlToPurge($unreachableUrl);
        $purger->addUrlToPurge($this->server->url('men.html'));
        $purger->purge();

        self::assertTrue($this->hasErrorAbout($unreachableUrl));
        self::assertSame(['/men.html'], array_column($this->server->requests(), 'target'));
    }

    private function createPurger(int $maxParallelRequests): VarnishUrlPurger
    {
        $generalConfig = $this->createStub(GeneralConfigProviderInterface::class);
        $generalConfig->method('getMaxConcurrentPurgeProcesses')->willReturn($maxParallelRequests);

        $purgingConfig = $this->createStub(PurgingConfigProviderInterface::class);
        $purgingConfig->method('isPurgeCustomHostEnabled')->willReturnCallback(fn (): bool => $this->customPurgeHostEnabled);
        $purgingConfig->method('getAdditionalHostForHeader')->willReturnCallback(fn (): string => $this->hostForHeader);
        $purgingConfig->method('getCustomPurgeHosts')->willReturn([]);

        $this->logRecords = new TestHandler();

        return Instantiator::create(VarnishUrlPurger::class, [
            GeneralConfigProviderInterface::class => $generalConfig,
            PurgingConfigProviderInterface::class => $purgingConfig,
            LoggerInterface::class => new Logger('varnish-warmer-test', [$this->logRecords]),
            QueueProgressLoggerInterface::class => $this->createStub(QueueProgressLoggerInterface::class),
            ReactLoopFactory::class => static fn (): ReactLoopFactory => new ReactLoopFactory(),
            ReactClientFactory::class => static fn (): ReactClientFactory => new ReactClientFactory(),
        ]);
    }

    private function loggedResponseCode(string $url): mixed
    {
        foreach ($this->logRecords->getRecords() as $record) {
            if (($record['context']['url'] ?? null) === $url) {
                return $record['context']['code'] ?? null;
            }
        }

        self::fail('Nothing was logged for ' . $url);
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
