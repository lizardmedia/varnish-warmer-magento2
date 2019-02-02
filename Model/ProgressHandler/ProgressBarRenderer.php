<?php
/**
 * File: ProgressBarRenderer.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2019 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Model\ProgressHandler;

use LizardMedia\VarnishWarmer\Api\ProgressHandler\ProgressBarRendererInterface;
use LizardMedia\VarnishWarmer\Api\ProgressHandler\ProgressDataInterface;

/**
 * Class ProgressBarRenderer
 * @package LizardMedia\VarnishWarmer\Model\ProgressHandler
 */
class ProgressBarRenderer implements ProgressBarRendererInterface
{
    /**
     * @param ProgressDataInterface $progressData
     * @return string
     */
    public function getProgressHtml(ProgressDataInterface $progressData): string
    {
        return $progressData->getCurrent()
            ? "{$progressData->getProcessType()} progress: "
                . "<progress max=\"{$progressData->getTotal()}\" value=\"{$progressData->getCurrent()}\"></progress> "
                . "({$progressData->getCurrent()}/{$progressData->getTotal()})"
            : '';
    }
}
