<?php
/**
 * File: ProgressBarRendererInterface.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2019 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Api\ProgressHandler;

/**
 * Interface ProgressBarRendererInterface
 * @package LizardMedia\VarnishWarmer\Api\ProgressHandler
 */
interface ProgressBarRendererInterface
{
    /**
     * @param ProgressDataInterface $progressData
     * @return string
     */
    public function getProgressHtml(ProgressDataInterface $progressData): string;
}
