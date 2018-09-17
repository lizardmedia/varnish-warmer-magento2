<?php
/**
 * File: PurgingConfigProviderInterface.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2018 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Api\Config;

/**
 * Interface PurgingConfigProviderInterface
 * @package LizardMedia\VarnishWarmer\Api\Config
 */
interface PurgingConfigProviderInterface
{
    /**
     * @return bool
     */
    public function isPurgeCustomHostEnabled(): bool;

    /**
     * @return array
     */
    public function getCustomPurgeHosts(): array;

    /**
     * @return string
     */
    public function getAdditionalHostForHeader(): string;
}
