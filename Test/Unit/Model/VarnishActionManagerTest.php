<?php

declare(strict_types=1);

/**
 * @author Paweł Majcherczyk <pawel.majcherczyk@lizardmedia.pl>
 * @copyright Copyright (C) 2026 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Test\Unit\Model;

use Exception;
use LizardMedia\VarnishWarmer\Api\Config\PurgingConfigProviderInterface;
use LizardMedia\VarnishWarmer\Api\LockHandler\LockInterface;
use LizardMedia\VarnishWarmer\Api\UrlProvider\CategoryUrlProviderInterface;
use LizardMedia\VarnishWarmer\Api\UrlProvider\ProductUrlProviderInterface;
use LizardMedia\VarnishWarmer\Model\QueueHandler\VarnishUrlPurger;
use LizardMedia\VarnishWarmer\Model\QueueHandler\VarnishUrlPurgerFactory;
use LizardMedia\VarnishWarmer\Model\QueueHandler\VarnishUrlRegenerator;
use LizardMedia\VarnishWarmer\Model\QueueHandler\VarnishUrlRegeneratorFactory;
use LizardMedia\VarnishWarmer\Model\VarnishActionManager;
use LizardMedia\VarnishWarmer\Test\Unit\Instantiator;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Throwable;

final class VarnishActionManagerTest extends TestCase
{
    private const BASE_URL = 'https://shop.test/';
    private const PRODUCT_ID = 42;

    private array $events = [];
    private array $purgedUrls = [];
    private array $regeneratedUrls = [];
    private array $regenerationStoreIds = [];
    private bool $locked = false;
    private array $secureBaseUrls = [1 => self::BASE_URL];
    private array $storeCodes = [1 => 'default', 2 => 'en', 3 => 'de'];
    private bool $storeCodeInUrlGlobally = false;
    private array $storeCodeInUrlForStoreIds = [];
    private bool $purgeEnabled = true;
    private bool $customPurgeHostsEnabled = false;
    private array $customPurgeHosts = [];
    private ?Throwable $categoryUrlsFailure = null;
    private array $categoryUrls = [['request_path' => 'women.html'], ['request_path' => 'men.html']];

    public function testPurgeHomepagePurgesAndRegeneratesHomepageUnderLock(): void
    {
        $this->createManager()->purgeHomepage();

        $this->assertPurged([self::BASE_URL]);
        $this->assertRegenerated([self::BASE_URL]);
        $this->assertRanUnderLock();
    }

    public function testPurgeGeneralPurgesAndRegeneratesHomepageAndCategoriesUnderLock(): void
    {
        $this->createManager()->purgeGeneral();

        $expectedUrls = [self::BASE_URL, self::BASE_URL . 'women.html', self::BASE_URL . 'men.html'];
        $this->assertPurged($expectedUrls);
        $this->assertRegenerated($expectedUrls);
        $this->assertRanUnderLock();
    }

    public function testPurgeAllPurgesAndRegeneratesHomepageCategoriesAndProductsUnderLock(): void
    {
        $this->createManager()->purgeAll();

        $expectedUrls = [
            self::BASE_URL,
            self::BASE_URL . 'women.html',
            self::BASE_URL . 'men.html',
            self::BASE_URL . 'shirt.html',
        ];
        $this->assertPurged($expectedUrls);
        $this->assertRegenerated($expectedUrls);
        $this->assertRanUnderLock();
    }

    public function testPurgeWildcardPurgesEverythingAndRegeneratesHomepageCategoriesAndProductsUnderLock(): void
    {
        $this->createManager()->purgeWildcard();

        $this->assertPurged([self::BASE_URL . '*']);
        $this->assertRegenerated([
            self::BASE_URL,
            self::BASE_URL . 'women.html',
            self::BASE_URL . 'men.html',
            self::BASE_URL . 'shirt.html',
        ]);
        $this->assertRanUnderLock();
    }

    public function testPurgeWildcardWithoutRegenerationOnlyPurgesEverythingAndSkipsLock(): void
    {
        $this->createManager()->purgeWildcardWithoutRegen();

        $this->assertPurged([self::BASE_URL . '*']);
        $this->assertRegenerated([]);
        $this->assertRanWithoutLock();
    }

    public function testPurgeAndRegenerateProductsHandlesOnlyProductsUnderLock(): void
    {
        $this->createManager()->purgeAndRegenerateProducts();

        $this->assertPurged([self::BASE_URL . 'shirt.html']);
        $this->assertRegenerated([self::BASE_URL . 'shirt.html']);
        $this->assertRanUnderLock();
    }

    public function testPurgeAndRegenerateUrlHandlesSingleUrlWithoutLock(): void
    {
        $this->createManager()->purgeAndRegenerateUrl('bizuteria');

        $this->assertPurged([self::BASE_URL . 'bizuteria']);
        $this->assertRegenerated([self::BASE_URL . 'bizuteria']);
        $this->assertRanWithoutLock();
    }

    public function testPurgeProductHandlesAllUrlsOfProductWithoutLock(): void
    {
        $product = $this->createStub(ProductInterface::class);
        $product->method('getId')->willReturn(self::PRODUCT_ID);

        $this->createManager()->purgeProduct($product);

        $expectedUrls = [self::BASE_URL . 'shirt.html', self::BASE_URL . 'women/shirt.html'];
        $this->assertPurged($expectedUrls);
        $this->assertRegenerated($expectedUrls);
        $this->assertRanWithoutLock();
    }

    public function testAddsMissingTrailingSlashToBaseUrl(): void
    {
        $this->secureBaseUrls[1] = 'https://shop.test';

        $this->createManager()->purgeAndRegenerateUrl('bizuteria');

        $this->assertPurged(['https://shop.test/bizuteria']);
        $this->assertRegenerated(['https://shop.test/bizuteria']);
    }

    public function testUsesBaseUrlOfSelectedStoreView(): void
    {
        $this->secureBaseUrls[3] = 'https://shop.de/';
        $manager = $this->createManager();

        $manager->setStoreViewId(3);
        $manager->purgeAndRegenerateUrl('schmuck');

        $this->assertPurged(['https://shop.de/schmuck']);
        $this->assertRegenerated(['https://shop.de/schmuck']);
    }

    public function testPrefixesPathWithStoreCodeWhenStoreCodeIsUsedInUrls(): void
    {
        $this->storeCodeInUrlGlobally = true;

        $this->createManager()->purgeAndRegenerateUrl('bizuteria');

        $this->assertPurged([self::BASE_URL . 'default/bizuteria']);
        $this->assertRegenerated([self::BASE_URL . 'default/bizuteria']);
    }

    public function testPurgesOnEveryCustomHostAndRegeneratesOnStoreBaseUrl(): void
    {
        $this->customPurgeHostsEnabled = true;
        $this->customPurgeHosts = ['http://varnish-1:6081', 'http://varnish-2:6081/'];

        $this->createManager()->purgeAndRegenerateUrl('bizuteria');

        $this->assertPurged(['http://varnish-1:6081/bizuteria', 'http://varnish-2:6081/bizuteria']);
        $this->assertRegenerated([self::BASE_URL . 'bizuteria']);
    }

    public function testDelegatesLockStateToLockHandler(): void
    {
        $lock = $this->createMock(LockInterface::class);
        $lock->expects(self::once())->method('isLocked')->willReturn(true);
        $lock->expects(self::once())->method('getLockDate')->willReturn('2026-10-22 10:15:00');

        $manager = $this->createManager($lock);

        self::assertTrue($manager->isLocked());
        self::assertSame('2026-10-22 10:15:00', $manager->getLockMessage());
    }

    public function testOnlyRegeneratesWhenPurgingIsDisabled(): void
    {
        $this->purgeEnabled = false;

        $this->createManager()->purgeAll();

        $this->assertPurged([]);
        $this->assertRegenerated([
            self::BASE_URL,
            self::BASE_URL . 'women.html',
            self::BASE_URL . 'men.html',
            self::BASE_URL . 'shirt.html',
        ]);
        $this->assertRanUnderLock();
    }

    public function testOnlyRegeneratesSingleUrlWhenPurgingIsDisabled(): void
    {
        $this->purgeEnabled = false;

        $this->createManager()->purgeAndRegenerateUrl('bizuteria');

        $this->assertPurged([]);
        $this->assertRegenerated([self::BASE_URL . 'bizuteria']);
    }

    public function testUsesOnlyUrlRewritesOfSelectedStoreView(): void
    {
        $this->secureBaseUrls[2] = 'https://shop.test/en/';
        $this->categoryUrls = [
            ['request_path' => 'kobiety.html', 'store_id' => '1'],
            ['request_path' => 'women.html', 'store_id' => '2'],
        ];
        $manager = $this->createManager();

        $manager->setStoreViewId(2);
        $manager->purgeGeneral();

        $this->assertPurged(['https://shop.test/en/', 'https://shop.test/en/women.html']);
        $this->assertRegenerated(['https://shop.test/en/', 'https://shop.test/en/women.html']);
    }

    public function testSwitchingStoreViewOnSameInstanceUsesBaseUrlOfNewStoreView(): void
    {
        $this->secureBaseUrls[3] = 'https://shop.de/';
        $manager = $this->createManager();

        $manager->purgeAndRegenerateUrl('bizuteria');
        $manager->setStoreViewId(3);
        $manager->purgeAndRegenerateUrl('schmuck');

        $this->assertRegenerated([self::BASE_URL . 'bizuteria', 'https://shop.de/schmuck']);
    }

    public function testTellsRegenerationWhichStoreViewEveryUrlBelongsTo(): void
    {
        $this->secureBaseUrls[3] = 'https://shop.de/';
        $manager = $this->createManager();

        $manager->setStoreViewId(3);
        $manager->purgeGeneral();

        self::assertSame([3, 3, 3], $this->regenerationStoreIds);
    }

    public function testReleasesLockWhenCollectingUrlsFails(): void
    {
        $this->categoryUrlsFailure = new Exception('Database connection lost');
        $manager = $this->createManager();

        $failure = null;
        try {
            $manager->purgeGeneral();
        } catch (Throwable $exception) {
            $failure = $exception;
        }

        self::assertNotNull($failure);
        self::assertFalse($this->locked);
    }

    public function testRegeneratesEveryUrlOnceRegardlessOfNumberOfPurgeHosts(): void
    {
        $this->customPurgeHostsEnabled = true;
        $this->customPurgeHosts = ['http://varnish-1:6081', 'http://varnish-2:6081'];

        $this->createManager()->purgeGeneral();

        $this->assertRegenerated([self::BASE_URL, self::BASE_URL . 'women.html', self::BASE_URL . 'men.html']);
    }

    public function testReadsStoreCodeInUrlSettingForSelectedStoreView(): void
    {
        $this->secureBaseUrls[2] = self::BASE_URL;
        $this->storeCodeInUrlForStoreIds = [2];
        $manager = $this->createManager();

        $manager->setStoreViewId(2);
        $manager->purgeAndRegenerateUrl('bizuteria');

        $this->assertPurged([self::BASE_URL . 'en/bizuteria']);
        $this->assertRegenerated([self::BASE_URL . 'en/bizuteria']);
    }

    private function createManager(?LockInterface $lock = null): VarnishActionManager
    {
        return Instantiator::create(VarnishActionManager::class, [
            VarnishUrlPurgerFactory::class => $this->createPurgerFactory(),
            VarnishUrlRegeneratorFactory::class => $this->createRegeneratorFactory(),
            LockInterface::class => $lock ?? $this->createRecordingLock(),
            ScopeConfigInterface::class => $this->createScopeConfig(),
            StoreManagerInterface::class => $this->createStoreManager(),
            ProductUrlProviderInterface::class => $this->createProductUrlProvider(),
            CategoryUrlProviderInterface::class => $this->createCategoryUrlProvider(),
            PurgingConfigProviderInterface::class => $this->createPurgingConfig(),
        ]);
    }

    private function createPurgerFactory(): VarnishUrlPurgerFactory
    {
        $purger = $this->createStub(VarnishUrlPurger::class);
        $purger->method('addUrlToPurge')->willReturnCallback(function (string $url): void {
            $this->purgedUrls[] = $url;
        });
        $purger->method('purge')->willReturnCallback(function (): void {
            $this->events[] = 'purge';
        });

        $factory = $this->createStub(VarnishUrlPurgerFactory::class);
        $factory->method('create')->willReturn($purger);

        return $factory;
    }

    private function createRegeneratorFactory(): VarnishUrlRegeneratorFactory
    {
        $regenerator = $this->createStub(VarnishUrlRegenerator::class);
        $regenerator->method('addUrlToRegenerate')->willReturnCallback(
            function (string $url, ?int $storeId = null): void {
                $this->regeneratedUrls[] = $url;
                $this->regenerationStoreIds[] = $storeId;
            }
        );
        $regenerator->method('regenerate')->willReturnCallback(function (): void {
            $this->events[] = 'regenerate';
        });

        $factory = $this->createStub(VarnishUrlRegeneratorFactory::class);
        $factory->method('create')->willReturn($regenerator);

        return $factory;
    }

    private function createRecordingLock(): LockInterface
    {
        $lock = $this->createStub(LockInterface::class);
        $lock->method('lock')->willReturnCallback(function (): void {
            $this->events[] = 'lock';
            $this->locked = true;
        });
        $lock->method('unlock')->willReturnCallback(function (): void {
            $this->events[] = 'unlock';
            $this->locked = false;
        });

        return $lock;
    }

    private function createScopeConfig(): ScopeConfigInterface
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(
            fn (string $path, string $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null) =>
                $path === Store::XML_PATH_SECURE_BASE_URL
                    ? $this->secureBaseUrls[$this->storeIdOf($scopeCode)] ?? null
                    : null
        );
        $scopeConfig->method('isSetFlag')->willReturnCallback(
            fn (string $path, string $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null): bool =>
                $path === Store::XML_PATH_STORE_IN_URL && $this->isStoreCodeInUrl($this->storeIdOf($scopeCode))
        );

        return $scopeConfig;
    }

    private function createStoreManager(): StoreManagerInterface
    {
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturnCallback(function ($storeId = null): Store {
            $storeId = $this->storeIdOf($storeId) ?? 1;
            if (!isset($this->storeCodes[$storeId])) {
                throw new NoSuchEntityException(new Phrase('The store that was requested wasn\'t found.'));
            }

            $store = $this->createStub(Store::class);
            $store->method('getId')->willReturn($storeId);
            $store->method('getCode')->willReturn($this->storeCodes[$storeId]);
            $store->method('isUseStoreInUrl')->willReturn($this->isStoreCodeInUrl($storeId));

            return $store;
        });

        return $storeManager;
    }

    private function createProductUrlProvider(): ProductUrlProviderInterface
    {
        $provider = $this->createStub(ProductUrlProviderInterface::class);
        $provider->method('getActiveProductsUrls')->willReturn([['request_path' => 'shirt.html']]);
        $provider->method('getProductUrls')->willReturnCallback(
            static fn (int $productId): array => $productId === self::PRODUCT_ID
                ? [['request_path' => 'shirt.html'], ['request_path' => 'women/shirt.html']]
                : []
        );

        return $provider;
    }

    private function createCategoryUrlProvider(): CategoryUrlProviderInterface
    {
        $provider = $this->createStub(CategoryUrlProviderInterface::class);
        $provider->method('getActiveCategoriesUrls')->willReturnCallback(function (): array {
            if ($this->categoryUrlsFailure !== null) {
                throw $this->categoryUrlsFailure;
            }

            return $this->categoryUrls;
        });

        return $provider;
    }

    private function createPurgingConfig(): PurgingConfigProviderInterface
    {
        $purgingConfig = $this->createStub(PurgingConfigProviderInterface::class);
        $purgingConfig->method('isPurgeEnabled')->willReturnCallback(fn (): bool => $this->purgeEnabled);
        $purgingConfig->method('isPurgeCustomHostEnabled')->willReturnCallback(fn (): bool => $this->customPurgeHostsEnabled);
        $purgingConfig->method('getCustomPurgeHosts')->willReturnCallback(fn (): array => $this->customPurgeHosts);
        $purgingConfig->method('getAdditionalHostForHeader')->willReturn('');

        return $purgingConfig;
    }

    private function storeIdOf(mixed $scope): ?int
    {
        if ($scope instanceof StoreInterface) {
            return (int) $scope->getId();
        }
        if (is_numeric($scope)) {
            return (int) $scope;
        }
        if (is_string($scope) && $scope !== '') {
            $storeId = array_search($scope, $this->storeCodes, true);

            return $storeId === false ? null : $storeId;
        }

        return null;
    }

    private function isStoreCodeInUrl(?int $storeId): bool
    {
        return $this->storeCodeInUrlGlobally
            || ($storeId !== null && in_array($storeId, $this->storeCodeInUrlForStoreIds, true));
    }

    private function assertPurged(array $expectedUrls): void
    {
        self::assertEqualsCanonicalizing($expectedUrls, $this->purgedUrls, 'URLs sent to Varnish purge');
    }

    private function assertRegenerated(array $expectedUrls): void
    {
        self::assertEqualsCanonicalizing($expectedUrls, $this->regeneratedUrls, 'URLs sent to regeneration');
    }

    private function assertRanUnderLock(): void
    {
        self::assertSame(['lock', 'purge', 'regenerate', 'unlock'], $this->events);
    }

    private function assertRanWithoutLock(): void
    {
        self::assertNotContains('lock', $this->events);
        self::assertSame('purge', $this->events[0] ?? null, 'Purge runs first');
    }
}
