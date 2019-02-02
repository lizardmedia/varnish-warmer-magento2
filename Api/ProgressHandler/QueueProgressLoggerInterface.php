<?php
/**
 * File: QueueProgressLoggerInterface.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2019 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Api\ProgressHandler;

/**
 * Interface QueueProgressLoggerInterface
 * @package LizardMedia\VarnishWarmer\Api\ProgressHandler
 */
interface QueueProgressLoggerInterface
{
    /**
     * @param string $type
     * @param int $current
     * @param int $total
     * @return void
     */
    public function logProgress(string $type, int $current, int $total): void;

    /**
     * @return ProgressDataInterface
     */
    public function getProgressData(): ProgressDataInterface;
}
