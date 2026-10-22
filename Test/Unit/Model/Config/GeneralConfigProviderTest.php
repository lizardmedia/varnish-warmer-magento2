<?php

declare(strict_types=1);

/**
 * @author Paweł Majcherczyk <pawel.majcherczyk@lizardmedia.pl>
 * @copyright Copyright (C) 2026 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Test\Unit\Model\Config;

use LizardMedia\VarnishWarmer\Model\Config\GeneralConfigProvider;
use LizardMedia\VarnishWarmer\Test\Unit\Instantiator;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class GeneralConfigProviderTest extends TestCase
{
    private array $config = [];

    public static function regenerationProcesses(): array
    {
        return [
            'minimum' => ['1', 1],
            'maximum' => ['20', 20],
            'within range' => ['7', 7],
            'below range' => ['0', 10],
            'above range' => ['21', 10],
            'not a number' => ['many', 10],
            'not set' => [null, 10],
        ];
    }

    public static function purgeProcesses(): array
    {
        return [
            'minimum' => ['1', 1],
            'maximum' => ['20', 20],
            'within range' => ['7', 7],
            'below range' => ['0', 4],
            'above range' => ['21', 4],
            'not a number' => ['many', 4],
            'not set' => [null, 4],
        ];
    }

    #[DataProvider('regenerationProcesses')]
    public function testLimitsConcurrentRegenerationToValidRange(?string $configured, int $expected): void
    {
        $this->config['lm_varnish/general/max_concurrent_regeneration'] = $configured;

        self::assertSame($expected, $this->createProvider()->getMaxConcurrentRegenerationProcesses());
    }

    #[DataProvider('purgeProcesses')]
    public function testLimitsConcurrentPurgeToValidRange(?string $configured, int $expected): void
    {
        $this->config['lm_varnish/general/max_concurrent_purge'] = $configured;

        self::assertSame($expected, $this->createProvider()->getMaxConcurrentPurgeProcesses());
    }

    public function testReadsExclude301RedirectsFlag(): void
    {
        $this->config['lm_varnish/general/exclude_301_redirects'] = '1';
        self::assertTrue($this->createProvider()->exclude301Redirects());

        $this->config['lm_varnish/general/exclude_301_redirects'] = '0';
        self::assertFalse($this->createProvider()->exclude301Redirects());
    }

    public function testReadsHttpAuthenticationCredentialsWithDecryptedPassword(): void
    {
        self::assertSame('', $this->createProvider()->getHttpAuthUsername());

        $this->config['lm_varnish/general/http_auth_username'] = 'stage';
        $this->config['lm_varnish/general/http_auth_password'] = 'encrypted-secret';

        self::assertSame('stage', $this->createProvider()->getHttpAuthUsername());
        self::assertSame('decrypted(encrypted-secret)', $this->createProvider()->getHttpAuthPassword());
    }

    private function createProvider(): GeneralConfigProvider
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(fn (string $path) => $this->config[$path] ?? null);
        $scopeConfig->method('isSetFlag')->willReturnCallback(fn (string $path): bool => (bool) ($this->config[$path] ?? false));

        $encryptor = $this->createStub(EncryptorInterface::class);
        $encryptor->method('decrypt')->willReturnCallback(static fn (string $data): string => "decrypted($data)");

        return Instantiator::create(GeneralConfigProvider::class, [
            ScopeConfigInterface::class => $scopeConfig,
            EncryptorInterface::class => $encryptor,
        ]);
    }
}
