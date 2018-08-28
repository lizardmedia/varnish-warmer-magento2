<?php
/**
 * File: CacheCleaner.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2018 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Helper;

use LizardMedia\VarnishWarmer\Api\Config\PurgingConfigProviderInterface;
use LizardMedia\VarnishWarmer\Api\LockHandler\LockInterface;
use LizardMedia\VarnishWarmer\Api\QueueHandler\VarnishUrlPurgerInterface;
use LizardMedia\VarnishWarmer\Api\QueueHandler\VarnishUrlRegeneratorInterface;
use LizardMedia\VarnishWarmer\Api\UrlProvider\CategoryUrlProviderInterface;
use LizardMedia\VarnishWarmer\Api\UrlProvider\ProductUrlProviderInterface;
use LizardMedia\VarnishWarmer\Model\QueueHandler\VarnishUrlRegeneratorFactory;
use LizardMedia\VarnishWarmer\Model\QueueHandler\VarnishUrlPurgerFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;

/**
 * Class CacheCleaner
 * @package LizardMedia\VarnishWarmer\Helper
 */
class CacheCleaner
{
    /**
     * @var VarnishUrlRegeneratorInterface
     */
    protected $varnishUrlRegenerator;

    /**
     * @var VarnishUrlPurgerInterface
     */
    protected $varnishUrlPurger;

    /**
     * @var LockInterface
     */
    protected $lockHandler;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var ProductUrlProviderInterface
     */
    protected $productUrlProvider;

    /**
     * @var CategoryUrlProviderInterface
     */
    protected $categoryUrlProvider;

    /**
     * @var PurgingConfigProviderInterface
     */
    protected $purgingConfigProvider;

    /**
     * @var string
     */
    protected $purgeBaseUrl;

    /**
     * @var string
     */
    protected $regenBaseUrl;

    /**
     * @var int
     */
    protected $storeViewId;

    /**
     * @var bool
     */
    public $verifyPeer = true;

    /**
     * CacheCleaner constructor.
     * @param VarnishUrlRegeneratorFactory $varnishUrlRegeneratorFactory
     * @param VarnishUrlPurgerFactory $varnishUrlPurgerFactory
     * @param LockInterface $lockHandler
     * @param ScopeConfigInterface $scopeConfig
     * @param ProductUrlProviderInterface $productUrlProvider
     * @param CategoryUrlProviderInterface $categoryUrlProvider
     */
    public function __construct(
        VarnishUrlRegeneratorFactory $varnishUrlRegeneratorFactory,
        VarnishUrlPurgerFactory $varnishUrlPurgerFactory,
        LockInterface $lockHandler,
        ScopeConfigInterface $scopeConfig,
        ProductUrlProviderInterface $productUrlProvider,
        CategoryUrlProviderInterface $categoryUrlProvider,
        PurgingConfigProviderInterface $purgingConfigProvider
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
    }

    /**
     * @param int $storeViewId
     */
    public function setStoreViewId(int $storeViewId)
    {
        $this->storeViewId = $storeViewId;
    }

    /**
     * Purge *
     * Regen homepage, categories, products
     * @return void
     */
    public function purgeWildcard(): void
    {
        $this->lock();
        $this->addUrlToPurge('*');
        $this->addUrlToRegenerate('');
        $this->regenerateCategories();
        $this->processProductsRegenerate();
        $this->varnishUrlPurger->setVerifyPeer($this->verifyPeer);
        $this->varnishUrlPurger->runPurgeQueue();
        $this->varnishUrlRegenerator->runRegenerationQueue();
        $this->unlock();
    }

    /**
     * Purge * without any regeneration
     * Pass through lock
     * @return void
     */
    public function purgeWildcardWithoutRegen(): void
    {
        $this->addUrlToPurge('*');
        $this->varnishUrlPurger->setVerifyPeer($this->verifyPeer);
        $this->varnishUrlPurger->runPurgeQueue();
    }

    /**
     * Purge homepage, categories, products
     * Regen homepage, categories, products
     * @return void
     */
    public function purgeAll(): void
    {
        $this->lock();
        $this->addUrlToPurge('');
        $this->addUrlToRegenerate('');
        $this->processCategoriesPurgeAndRegenerate();
        $this->processProductsPurgeAndRegenerate();
        $this->varnishUrlPurger->setVerifyPeer($this->verifyPeer);
        $this->varnishUrlPurger->runPurgeQueue();
        $this->varnishUrlRegenerator->runRegenerationQueue();
        $this->unlock();
    }

    /**
     * Purge homepage, categories
     * Regen homepage, categories
     * @return void
     */
    public function purgeGeneral(): void
    {
        $this->lock();
        $this->addUrlToPurge('');
        $this->addUrlToRegenerate('');
        $this->processCategoriesPurgeAndRegenerate();
        $this->varnishUrlPurger->setVerifyPeer($this->verifyPeer);
        $this->varnishUrlPurger->runPurgeQueue();
        $this->varnishUrlRegenerator->runRegenerationQueue();
        $this->unlock();
    }

    /**
     * Purge homepage
     * Regen homepage
     * @return void
     */
    public function purgeHomepage(): void
    {
        $this->lock();
        $this->addUrlToPurge('');
        $this->addUrlToRegenerate('');
        $this->varnishUrlPurger->setVerifyPeer($this->verifyPeer);
        $this->varnishUrlPurger->runPurgeQueue();
        $this->varnishUrlRegenerator->runRegenerationQueue();
        $this->unlock();
    }

    /**
     * @param string $url
     * @return void
     */
    public function purgeAndRegenerateUrl(string $url): void
    {
        $this->addUrlToPurge($url);
        $this->addUrlToRegenerate($url);
        $this->varnishUrlPurger->setVerifyPeer($this->verifyPeer);
        $this->varnishUrlPurger->runPurgeQueue();
        $this->varnishUrlRegenerator->runRegenerationQueue();
    }

    /**
     * @param $product
     * @return void
     */
    public function purgeProduct($product): void
    {
        $productUrls = $this->getProductUrls($product->getEntityId());
        foreach ($productUrls as $url) {
            $this->addUrlToPurge($url['request_path'], true);
        }
        $this->varnishUrlPurger->runPurgeQueue();
        $this->varnishUrlRegenerator->runRegenerationQueue();
    }

    /**
     * @return void
     */
    public function purgeAndRegenerateProducts(): void
    {
        $this->lock();
        $this->processProductsPurgeAndRegenerate();
        $this->varnishUrlPurger->setVerifyPeer($this->verifyPeer);
        $this->varnishUrlPurger->runPurgeQueue();
        $this->varnishUrlRegenerator->runRegenerationQueue();
        $this->unlock();
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
     * @param $relativeUrl
     * @param bool $autoRegenerate
     * @return void
     */
    private function addUrlToPurge($relativeUrl, $autoRegenerate = false): void
    {
        $url = $this->getPurgeBaseUrl() . $relativeUrl;
        $this->varnishUrlPurger->addUrlToPurge($url);
        if ($autoRegenerate) {
            $this->addUrlToRegenerate($relativeUrl);
        }
    }

    /**
     * @param $relativeUrl
     * @return void
     */
    private function addUrlToRegenerate($relativeUrl): void
    {
        $url = $this->getRegenBaseUrl() . $relativeUrl;
        $this->varnishUrlRegenerator->addUrlToRegenerate($url);
    }

    /**
     * @return void
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
     */
    private function processProductsRegenerate(): void
    {
        $productUrls = $this->getAllProductsUrls();
        foreach ($productUrls as $key => $url) {
            $this->addUrlToRegenerate($url['request_path']);
        }
    }

    /**
     * @return void
     */
    private function processProductsPurgeAndRegenerate(): void
    {
        $productUrls = $this->getAllProductsUrls();
        foreach ($productUrls as $key => $url) {
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
    private function getProductUrls($productId): array
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
    private function setPurgeBaseUrl(): void
    {
        if ($this->purgingConfigProvider->isPurgeCustomHostEnabled()) {
            $this->purgeBaseUrl = $this->purgingConfigProvider->getCustomPurgeHost();
        } else {
            $this->purgeBaseUrl = $this->scopeConfig->getValue(
                Store::XML_PATH_UNSECURE_BASE_URL,
                ScopeInterface::SCOPE_STORE,
                $this->storeViewId
            );
        }

        if (substr($this->purgeBaseUrl, -1) != "/") {
            $this->purgeBaseUrl .= "/";
        }
    }

    /**
     * @return void
     */
    private function setRegenBaseUrl(): void
    {
        $this->regenBaseUrl = $this->scopeConfig->getValue(
            Store::XML_PATH_UNSECURE_BASE_URL,
            ScopeInterface::SCOPE_STORE,
            $this->storeViewId
        );

        if (substr($this->regenBaseUrl, -1) != "/") {
            $this->regenBaseUrl .= "/";
        }
    }

    /**
     * @return string
     */
    private function getPurgeBaseUrl()
    {
        if (!$this->purgeBaseUrl) {
            $this->setPurgeBaseUrl();
        }
        return $this->purgeBaseUrl;
    }

    /**
     * @return string
     */
    private function getRegenBaseUrl()
    {
        if (!$this->regenBaseUrl) {
            $this->setRegenBaseUrl();
        }
        return $this->regenBaseUrl;
    }
}
