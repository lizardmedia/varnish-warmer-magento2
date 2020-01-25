<?php

declare(strict_types=1);

/**
 * File: PurgeWildcardForce.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2019 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Controller\Adminhtml\Purge;

use LizardMedia\VarnishWarmer\Console\Command\PurgeWildcardWithoutRegenerationCommand;
use LizardMedia\VarnishWarmer\Controller\Adminhtml\Purge;
use Magento\Framework\Controller\Result\Redirect;

/**
 * Class PurgeWildcardForce
 * @package LizardMedia\VarnishWarmer\Controller\Adminhtml\Purge
 */
class PurgeWildcardForce extends Purge
{
    /**
     * @return Redirect
     */
    public function execute(): Redirect
    {
        $this->runCommand();
        $this->addProcessNotification();

        return $this->getRedirect();
    }

    /**
     * @return void
     */
    protected function addProcessNotification(): void
    {
        $this->messageManager->addNoticeMessage(
            __('LizardMedia: cache is purged in background, it may take up to 20 seconds.')
        );
    }

    /**
     * @return string
     */
    protected function getCliCommand(): string
    {
        return PurgeWildcardWithoutRegenerationCommand::CLI_COMMAND;
    }
}
