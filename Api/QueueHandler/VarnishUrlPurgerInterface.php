<?php
/**
 * File: VarnishUrlPurgerInterface.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2019 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Api\QueueHandler;

/**
 * Interface VarnishUrlPurgerInterface
 * @package LizardMedia\VarnishWarmer\Api\QueueHandler
 */
interface VarnishUrlPurgerInterface
{
    /**
     * @param string $url
     * @return void
     */
    public function addUrlToPurge(string $url): void;

    /**
     * @return void
     */
    public function runPurgeQueue(): void;
}
