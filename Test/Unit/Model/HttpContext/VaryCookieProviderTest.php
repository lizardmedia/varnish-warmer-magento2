<?php

declare(strict_types=1);

/**
 * @author Paweł Majcherczyk <pawel.majcherczyk@lizardmedia.pl>
 * @copyright Copyright (C) 2026 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Test\Unit\Model\HttpContext;

use LizardMedia\VarnishWarmer\Model\HttpContext\VaryCookieProvider;
use LizardMedia\VarnishWarmer\Test\Unit\Instantiator;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\App\Http\ContextFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

final class VaryCookieProviderTest extends TestCase
{
    private array $createdContexts = [];
    private ?string $varyString = 'vary-hash';
    private bool $storeCodeInUrl = false;

    public function testComparesStoreViewWithDefaultStoreViewTheWayStorefrontDoes(): void
    {
        $varyCookies = $this->createProvider()->getVaryCookies(2);

        self::assertSame([null, 'vary-hash'], $varyCookies);
        self::assertSame(
            [[
                'data' => ['store' => 'en', 'current_currency' => 'EUR'],
                'default' => ['store' => 'pl', 'current_currency' => 'PLN'],
            ]],
            $this->createdContexts
        );
    }

    public function testTreatsStoreViewAsItsOwnDefaultWhenStoreCodeIsInUrl(): void
    {
        $this->storeCodeInUrl = true;

        $this->createProvider()->getVaryCookies(2);

        self::assertSame(
            ['store' => 'en', 'current_currency' => 'EUR'],
            $this->createdContexts[0]['default']
        );
    }

    public function testWarmsOnlyCookielessVariantWhenStoreViewDoesNotChangeContext(): void
    {
        $this->varyString = null;

        self::assertSame([null], $this->createProvider()->getVaryCookies(1));
    }

    public function testResolvesEveryStoreViewOnce(): void
    {
        $provider = $this->createProvider();

        $provider->getVaryCookies(2);
        $provider->getVaryCookies(2);

        self::assertCount(1, $this->createdContexts);
    }

    private function createProvider(): VaryCookieProvider
    {
        $stores = [1 => $this->createStore('pl', 'PLN'), 2 => $this->createStore('en', 'EUR')];

        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturnCallback(fn ($storeId): Store => $stores[(int) $storeId]);
        $storeManager->method('getDefaultStoreView')->willReturn($stores[1]);

        $contextFactory = $this->createStub(ContextFactory::class);
        $contextFactory->method('create')->willReturnCallback(function (array $arguments): HttpContext {
            $this->createdContexts[] = $arguments;
            $context = $this->createStub(HttpContext::class);
            $context->method('getVaryString')->willReturn($this->varyString);

            return $context;
        });

        return Instantiator::create(VaryCookieProvider::class, [
            StoreManagerInterface::class => $storeManager,
            ContextFactory::class => $contextFactory,
        ]);
    }

    private function createStore(string $code, string $currency): Store
    {
        $store = $this->createStub(Store::class);
        $store->method('getCode')->willReturn($code);
        $store->method('getDefaultCurrencyCode')->willReturn($currency);
        $store->method('isUseStoreInUrl')->willReturnCallback(fn (): bool => $this->storeCodeInUrl);

        return $store;
    }
}
