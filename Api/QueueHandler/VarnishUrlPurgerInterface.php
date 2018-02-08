<?php
/**
 * File: VarnishUrlPurgerInterface.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2018 Lizard Media (http://lizardmedia.pl)
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

    /**
     * @return bool
     */
    public function isVerifyPeer(): bool;

    /**
     * @param bool $verifyPeer
     * @return void
     */
    public function setVerifyPeer(bool $verifyPeer): void;
}
