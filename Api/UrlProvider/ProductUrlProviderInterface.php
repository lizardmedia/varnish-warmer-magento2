<?php
/**
 * File: ProductUrlProviderInterface.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2019 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Api\UrlProvider;

/**
 * Interface ProductUrlProviderInterface
 * @package LizardMedia\VarnishWarmer\Api\UrlProvider
 */
interface ProductUrlProviderInterface
{
    /**
     * @param int $productId
     * @return array
     */
    public function getProductUrls(int $productId): array;

    /**
     * @return array
     */
    public function getActiveProductsUrls(): array;
}
