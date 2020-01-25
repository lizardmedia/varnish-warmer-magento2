<?php

declare(strict_types=1);

/**
 * File:VarnishActionManager.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @author Bartosz Kubicki <bartosz.kubicki@lizardmedia.pl>
 * @copyright Copyright (C) 2019 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Model;

use LizardMedia\VarnishWarmer\Api\Config\PurgingConfigProviderInterface;
use LizardMedia\VarnishWarmer\Api\LockHandler\LockInterface;
use LizardMedia\VarnishWarmer\Api\QueueHandler\VarnishUrlPurgerInterface;
use LizardMedia\VarnishWarmer\Api\QueueHandler\VarnishUrlRegeneratorInterface;
use LizardMedia\VarnishWarmer\Api\UrlProvider\CategoryUrlProviderInterface;
use LizardMedia\VarnishWarmer\Api\UrlProvider\ProductUrlProviderInterface;
use LizardMedia\VarnishWarmer\Api\VarnishActionManagerInterface;
use LizardMedia\VarnishWarmer\Model\QueueHandler\VarnishUrlPurgerFactory;
use LizardMedia\VarnishWarmer\Model\QueueHandler\VarnishUrlRegeneratorFactory;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

/**
 * TODO: Refactor, decouple
 * Class VarnishActionManager
 * @package LizardMedia\VarnishWarmer\Model
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class VarnishActionManager implements VarnishActionManagerInterface
{
    /**
     * @var int
     */
    private const DEFAULT_FRONTEND_STORE_VIEW_ID = 1;

    /**
     * @var array
     */
    private $purgeBaseUrls;

    /**
     * @var string
     */
    private $regenBaseUrl;

    /**
     * @var int
     */
    private $storeViewId;

    /**
     * @var bool
     */
    private $isStoreCodeUsedInUrls;

    /**
     * @var StoreInterface
     */
    private $currentStoreView;

    /**
     * @var VarnishUrlRegeneratorInterface
     */
    private $varnishUrlRegenerator;

    /**
     * @var VarnishUrlPurgerInterface
     */
    private $varnishUrlPurger;

    /**
     * @var LockInterface
     */
    private $lockHandler;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ProductUrlProviderInterface
     */
    private $productUrlProvider;

    /**
     * @var CategoryUrlProviderInterface
     */
    private $categoryUrlProvider;

    /**
     * @var PurgingConfigProviderInterface
     */
    private $purgingConfigProvider;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * VarnishActionManager constructor.
     * @param VarnishUrlRegeneratorFactory $varnishUrlRegeneratorFactory
     * @param VarnishUrlPurgerFactory $varnishUrlPurgerFactory
     * @param LockInterface $lockHandler
     * @param ScopeConfigInterface $scopeConfig
     * @param ProductUrlProviderInterface $productUrlProvider
     * @param CategoryUrlProviderInterface $categoryUrlProvider
     * @param PurgingConfigProviderInterface $purgingConfigProvider
     * @param StoreManagerInterface $storeManager
     * @SuppressWarnings(PHPMD.LongVariable)
     * @throws NoSuchEntityException
     */
    public function __construct(
        VarnishUrlRegeneratorFactory $varnishUrlRegeneratorFactory,
        VarnishUrlPurgerFactory $varnishUrlPurgerFactory,
        LockInterface $lockHandler,
        ScopeConfigInterface $scopeConfig,
        ProductUrlProviderInterface $productUrlProvider,
        CategoryUrlProviderInterface $categoryUrlProvider,
        PurgingConfigProviderInterface $purgingConfigProvider,
        StoreManagerInterface $storeManager
    ) {
        $this->lockHandler = $lockHandler;
        $this->scopeConfig = $scopeConfig;
        $this->productUrlProvider = $productUrlProvider;
        $this->categoryUrlProvider = $categoryUrlProvider;
        $this->purgingConfigProvider = $purgingConfigProvider;

        /** @var VarnishUrlRegeneratorInterface varnishUrlRegenerator */
        $this->varnishUrlRegenerator = $varnishUrlRegeneratorFactory->create();
        /** @var VarnishUrlPurgerInterface varnishUrlPurger */
        $this->varnishUrlPurger = $varnishUrlPurgerFactory->create();
        $this->storeManager = $storeManager;
        $this->storeViewId = $this->getDefaultStoreViewId();
        $this->isStoreCodeUsedInUrls = $this->isStoreCodeUsedInUrls();
    }

    /**
     * Purge *
     * Regen homepage, categories, products
     * @return void
     * @throws NoSuchEntityException
     */
    public function purgeWildcard(): void
    {
        $this->lock();
        $this->addUrlToPurge('*');
        $this->addUrlToRegenerate('');
        $this->regenerateCategories();
        $this->processProductsRegenerate();
        $this->varnishUrlPurger->purge();
        $this->varnishUrlRegenerator->regenerate();
        $this->unlock();
    }

    /**
     * Purge * without any regeneration
     * Pass through lock
     * @return void
     * @throws NoSuchEntityException
     */
    public function purgeWildcardWithoutRegen(): void
    {
        $this->addUrlToPurge('*');
        $this->varnishUrlPurger->purge();
    }

    /**
     * Purge homepage, categories, products
     * Regen homepage, categories, products
     * @return void
     * @throws NoSuchEntityException
     */
    public function purgeAll(): void
    {
        $this->lock();
        $this->addUrlToPurge('');
        $this->addUrlToRegenerate('');
        $this->processCategoriesPurgeAndRegenerate();
        $this->processProductsPurgeAndRegenerate();
        $this->varnishUrlPurger->purge();
        $this->varnishUrlRegenerator->regenerate();
        $this->unlock();
    }

    /**
     * Purge homepage, categories
     * Regen homepage, categories
     * @return void
     * @throws NoSuchEntityException
     */
    public function purgeGeneral(): void
    {
        $this->lock();
        $this->addUrlToPurge('');
        $this->addUrlToRegenerate('');
        $this->processCategoriesPurgeAndRegenerate();
        $this->varnishUrlPurger->purge();
        $this->varnishUrlRegenerator->regenerate();
        $this->unlock();
    }

    /**
     * Purge homepage
     * Regen homepage
     * @return void
     * @throws NoSuchEntityException
     */
    public function purgeHomepage(): void
    {
        $this->lock();
        $this->addUrlToPurge('');
        $this->addUrlToRegenerate('');
        $this->varnishUrlPurger->purge();
        $this->varnishUrlRegenerator->regenerate();
        $this->unlock();
    }

    /**
     * @return void
     * @throws NoSuchEntityException
     */
    public function purgeAndRegenerateProducts(): void
    {
        $this->lock();
        $this->processProductsPurgeAndRegenerate();
        $this->varnishUrlPurger->purge();
        $this->varnishUrlRegenerator->regenerate();
        $this->unlock();
    }

    /**
     * @param string $url
     * @return void
     * @throws NoSuchEntityException
     */
    public function purgeAndRegenerateUrl(string $url): void
    {
        $this->addUrlToPurge($url);
        $this->addUrlToRegenerate($url);
        $this->varnishUrlPurger->purge();
        $this->varnishUrlRegenerator->regenerate();
    }

    /**
     * @param ProductInterface $product
     * @return void
     * @throws NoSuchEntityException
     */
    public function purgeProduct(ProductInterface $product): void
    {
        $productUrls = $this->getProductUrls((int) $product->getId());

        foreach ($productUrls as $url) {
            $this->addUrlToPurge($url['request_path'], true);
        }

        $this->varnishUrlPurger->purge();
        $this->varnishUrlRegenerator->regenerate();
    }

    /**
     * @param int $storeViewId
     */
    public function setStoreViewId(int $storeViewId): void
    {
        $this->storeViewId = $storeViewId;
    }

    /**
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->lockHandler->isLocked();
    }

    /**
     * @return string
     */
    public function getLockMessage(): string
    {
        return $this->lockHandler->getLockDate();
    }

    /**
     * @return int
     * @throws NoSuchEntityException
     */
    private function getDefaultStoreViewId(): int
    {
        $defaultStoreView = $this->storeManager->getStore(self::DEFAULT_FRONTEND_STORE_VIEW_ID);
        return $defaultStoreView instanceof StoreInterface
            ? (int) $defaultStoreView->getId()
            : 1;
    }

    /**
     * @return bool
     */
    private function isStoreCodeUsedInUrls(): bool
    {
        if (empty($this->isStoreCodeUsedInUrls)) {
            $this->isStoreCodeUsedInUrls = $this->scopeConfig->isSetFlag(Store::XML_PATH_STORE_IN_URL);
        }

        return $this->isStoreCodeUsedInUrls;
    }

    /**
     * @param $relativeUrl
     * @param bool $autoRegenerate
     * @return void
     * @throws NoSuchEntityException
     */
    private function addUrlToPurge(string $relativeUrl, bool $autoRegenerate = false): void
    {
        foreach ($this->getPurgeBaseUrls() as $purgeBaseUrl) {
            $url = $purgeBaseUrl . $this->buildStoreCodePartIfUsed() .  $relativeUrl;
            $this->varnishUrlPurger->addUrlToPurge($url);

            if ($autoRegenerate) {
                $this->addUrlToRegenerate($relativeUrl);
            }
        }
    }

    /**
     * @param string $relativeUrl
     * @return void
     * @throws NoSuchEntityException
     */
    private function addUrlToRegenerate(string $relativeUrl): void
    {
        $url = $this->getRegenBaseUrl() . $this->buildStoreCodePartIfUsed() . $relativeUrl;
        $this->varnishUrlRegenerator->addUrlToRegenerate($url);
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    private function buildStoreCodePartIfUsed(): string
    {
        if ($this->isStoreCodeUsedInUrls) {
            return sprintf('%s/', $this->getStoreCode());
        }

        return '';
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    private function getStoreCode(): string
    {
        $currentStore = $this->getCurrentStoreView();
        return $currentStore->getCode();
    }

    /**
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    private function getCurrentStoreView(): StoreInterface
    {
        if (!$this->currentStoreView instanceof StoreInterface) {
            $this->currentStoreView = $this->storeManager->getStore($this->storeViewId);
        }

        return $this->currentStoreView;
    }

    /**
     * @return void
     * @throws NoSuchEntityException
     */
    private function regenerateCategories(): void
    {
        $categories = $this->getCategories();

        foreach ($categories as $category) {
            $this->addUrlToRegenerate($category['request_path']);
        }
    }

    /**
     * @return void
     * @throws NoSuchEntityException
     */
    private function processCategoriesPurgeAndRegenerate(): void
    {
        $categories = $this->getCategories();

        foreach ($categories as $category) {
            $this->addUrlToPurge($category['request_path'], true);
        }
    }

    /**
     * @return void
     * @throws NoSuchEntityException
     */
    private function processProductsRegenerate(): void
    {
        $productUrls = $this->getAllProductsUrls();

        foreach ($productUrls as $url) {
            $this->addUrlToRegenerate($url['request_path']);
        }
    }

    /**
     * @return void
     * @throws NoSuchEntityException
     */
    private function processProductsPurgeAndRegenerate(): void
    {
        $productUrls = $this->getAllProductsUrls();

        foreach ($productUrls as $url) {
            $this->addUrlToPurge($url['request_path'], true);
        }
    }

    /**
     * @return array
     */
    private function getAllProductsUrls(): array
    {
        return $this->productUrlProvider->getActiveProductsUrls();
    }

    /**
     * @param $productId
     * @return array
     */
    private function getProductUrls(int $productId): array
    {
        return $this->productUrlProvider->getProductUrls($productId);
    }

    /**
     * @return array
     */
    private function getCategories(): array
    {
        return $this->categoryUrlProvider->getActiveCategoriesUrls();
    }

    /**
     * @return void
     */
    private function lock(): void
    {
        $this->lockHandler->lock();
    }

    /**
     * @return void
     */
    private function unlock(): void
    {
        $this->lockHandler->unlock();
    }

    /**
     * @return void
     */
    private function setPurgeBaseUrls(): void
    {
        if ($this->purgingConfigProvider->isPurgeCustomHostEnabled()) {
            $this->purgeBaseUrls = $this->purgingConfigProvider->getCustomPurgeHosts();
        } else {
            $baseUrl = $this->scopeConfig->getValue(
                Store::XML_PATH_SECURE_BASE_URL,
                ScopeInterface::SCOPE_STORE,
                $this->storeViewId
            );
            $this->purgeBaseUrls = [$baseUrl];
        }

        foreach ($this->purgeBaseUrls as &$purgeBaseUrl) {
            if (substr($purgeBaseUrl, -1) !== '/') {
                $purgeBaseUrl .= '/';
            }
        }
    }

    /**
     * @return void
     */
    private function setRegenBaseUrl(): void
    {
        $this->regenBaseUrl = (string) $this->scopeConfig->getValue(
            Store::XML_PATH_SECURE_BASE_URL,
            ScopeInterface::SCOPE_STORE,
            $this->storeViewId
        );

        if (substr($this->regenBaseUrl, -1) !== '/') {
            $this->regenBaseUrl .= '/';
        }
    }

    /**
     * @return array
     */
    private function getPurgeBaseUrls(): array
    {
        if (!$this->purgeBaseUrls) {
            $this->setPurgeBaseUrls();
        }

        return $this->purgeBaseUrls;
    }

    /**
     * @return string
     */
    private function getRegenBaseUrl(): string
    {
        if (!$this->regenBaseUrl) {
            $this->setRegenBaseUrl();
        }

        return $this->regenBaseUrl;
    }
}
