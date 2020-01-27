<?php
declare(strict_types=1);

/**
 * File:VarnishActionManagerInterface.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2019 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Api;

use Magento\Catalog\Api\Data\ProductInterface;

/**
 * Interface VarnishActionManagerInterface
 * @package LizardMedia\VarnishWarmer\Api
 */
interface VarnishActionManagerInterface
{
    /**
     * Purge *
     * Regen homepage, categories, products
     * @return void
     */
    public function purgeWildcard(): void;

    /**
     * Purge * without any regeneration
     * Pass through lock
     * @return void
     */
    public function purgeWildcardWithoutRegen(): void;

    /**
     * Purge homepage, categories, products
     * Regen homepage, categories, products
     * @return void
     */
    public function purgeAll(): void;

    /**
     * Purge homepage, categories
     * Regen homepage, categories
     * @return void
     */
    public function purgeGeneral(): void;

    /**
     * Purge homepage
     * Regen homepage
     * @return void
     */
    public function purgeHomepage(): void;

    /**
     * @return void
     */
    public function purgeAndRegenerateProducts(): void;

    /**
     * @param string $url
     * @return void
     */
    public function purgeAndRegenerateUrl(string $url): void;

    /**
     * @param ProductInterface $product
     * @return void
     */
    public function purgeProduct(ProductInterface $product): void;

    /**
     * @param int $storeViewId
     */
    public function setStoreViewId(int $storeViewId);

    /**
     * @return bool
     */
    public function isLocked(): bool;

    /**
     * @return string
     */
    public function getLockMessage(): string;
}
