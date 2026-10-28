<?php

declare(strict_types=1);

/**
 * @author Paweł Majcherczyk <pawel.majcherczyk@lizardmedia.pl>
 * @copyright Copyright (C) 2026 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Model\HttpContext;

use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\App\Http\ContextFactory;
use Magento\Store\Model\StoreManagerInterface;

class VaryCookieProvider
{
    private StoreManagerInterface $storeManager;
    private ContextFactory $contextFactory;
    private array $varyCookiesByStoreId = [];

    public function __construct(StoreManagerInterface $storeManager, ContextFactory $contextFactory)
    {
        $this->storeManager = $storeManager;
        $this->contextFactory = $contextFactory;
    }

    /**
     * @return array<int, string|null> X-Magento-Vary cookie values sent by guests of the store view, null for no cookie
     */
    public function getVaryCookies(int $storeId): array
    {
        if (!array_key_exists($storeId, $this->varyCookiesByStoreId)) {
            $this->varyCookiesByStoreId[$storeId] = $this->resolveVaryCookies($storeId);
        }

        return $this->varyCookiesByStoreId[$storeId];
    }

    private function resolveVaryCookies(int $storeId): array
    {
        $store = $this->storeManager->getStore($storeId);
        $defaultStore = $store->isUseStoreInUrl() ? $store : ($this->storeManager->getDefaultStoreView() ?? $store);

        $varyString = $this->contextFactory->create([
            'data' => [
                StoreManagerInterface::CONTEXT_STORE => $store->getCode(),
                HttpContext::CONTEXT_CURRENCY => $store->getDefaultCurrencyCode(),
            ],
            'default' => [
                StoreManagerInterface::CONTEXT_STORE => $defaultStore->getCode(),
                HttpContext::CONTEXT_CURRENCY => $defaultStore->getDefaultCurrencyCode(),
            ],
        ])->getVaryString();

        return $varyString === null ? [null] : [null, $varyString];
    }
}
