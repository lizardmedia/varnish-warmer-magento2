<?php
/**
 * File: CategoryUrlProviderInterface.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2019 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Api\UrlProvider;

/**
 * Interface CategoryUrlProviderInterface
 * @package LizardMedia\VarnishWarmer\Api\UrlProvider
 */
interface CategoryUrlProviderInterface
{
    /**
     * @return array
     */
    public function getActiveCategoriesUrls(): array;
}
