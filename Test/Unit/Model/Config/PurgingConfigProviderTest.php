<?php

declare(strict_types=1);

/**
 * @author Paweł Majcherczyk <pawel.majcherczyk@lizardmedia.pl>
 * @copyright Copyright (C) 2026 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Test\Unit\Model\Config;

use LizardMedia\VarnishWarmer\Model\Config\PurgingConfigProvider;
use LizardMedia\VarnishWarmer\Test\Unit\Instantiator;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

final class PurgingConfigProviderTest extends TestCase
{
    private array $config = [];

    public function testReadsWhetherCustomPurgeHostIsEnabled(): void
    {
        self::assertFalse($this->createProvider()->isPurgeCustomHostEnabled());

        $this->config['lm_varnish/purge/different_purge_host'] = '1';
        self::assertTrue($this->createProvider()->isPurgeCustomHostEnabled());

        $this->config['lm_varnish/purge/different_purge_host'] = '0';
        self::assertFalse($this->createProvider()->isPurgeCustomHostEnabled());
    }

    public function testSplitsCustomPurgeHostsOnComma(): void
    {
        $this->config['lm_varnish/purge/custom_host'] = 'http://varnish-1:6081,http://varnish-2:6081';

        self::assertSame(
            ['http://varnish-1:6081', 'http://varnish-2:6081'],
            $this->createProvider()->getCustomPurgeHosts()
        );
    }

    public function testReadsSingleCustomPurgeHost(): void
    {
        $this->config['lm_varnish/purge/custom_host'] = 'http://varnish:6081';

        self::assertSame(['http://varnish:6081'], $this->createProvider()->getCustomPurgeHosts());
    }

    public function testReadsHostForPurgeRequestHeader(): void
    {
        self::assertSame('', $this->createProvider()->getAdditionalHostForHeader());

        $this->config['lm_varnish/purge/header_host'] = 'shop.test';
        self::assertSame('shop.test', $this->createProvider()->getAdditionalHostForHeader());
    }

    #[Group('known-bug')]
    public function testIgnoresWhitespaceAroundCustomPurgeHosts(): void
    {
        $this->config['lm_varnish/purge/custom_host'] = 'http://varnish-1:6081, http://varnish-2:6081';

        self::assertSame(
            ['http://varnish-1:6081', 'http://varnish-2:6081'],
            $this->createProvider()->getCustomPurgeHosts()
        );
    }

    private function createProvider(): PurgingConfigProvider
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(fn (string $path) => $this->config[$path] ?? null);
        $scopeConfig->method('isSetFlag')->willReturnCallback(fn (string $path): bool => (bool) ($this->config[$path] ?? false));

        return Instantiator::create(PurgingConfigProvider::class, [ScopeConfigInterface::class => $scopeConfig]);
    }
}
