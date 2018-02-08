<?php
/**
 * File: LockInterface.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2018 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Api\LockHandler;

/**
 * Interface LockInterface
 * @package LizardMedia\VarnishWarmer\Api\LockHandler
 */
interface LockInterface
{
    /**
     * @return void
     */
    public function lock(): void;

    /**
     * @return void
     */
    public function unlock(): void;

    /**
     * @return bool
     */
    public function isLocked(): bool;

    /**
     * @return string
     */
    public function getLockDate(): string;
}
