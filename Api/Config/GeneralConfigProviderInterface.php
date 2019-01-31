<?php
/**
 * File: GeneralConfigProviderInterface.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2019 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Api\Config;

/**
 * Interface GeneralConfigProviderInterface
 * @package LizardMedia\VarnishWarmer\Api\Config
 */
interface GeneralConfigProviderInterface
{
    /**
     * @return int
     */
    public function getMaxConcurrentRegenerationProcesses(): int;

    /**
     * @return int
     */
    public function getMaxConcurrentPurgeProcesses(): int;
}
