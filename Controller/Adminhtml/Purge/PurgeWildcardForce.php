<?php
/**
 * File: PurgeWildcardForce.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2019 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Controller\Adminhtml\Purge;

use LizardMedia\VarnishWarmer\Console\Command\PurgeWildcardWithoutRegenerationCommand;
use LizardMedia\VarnishWarmer\Controller\Adminhtml\Purge;

/**
 * Class PurgeWildcardForce
 * @package LizardMedia\VarnishWarmer\Controller\Adminhtml\Purge
 */
class PurgeWildcardForce extends Purge
{
    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $this->runCommand();
        $this->addProcessNotification();

        return $this->getRedirect();
    }

    /**
     * @return null
     */
    protected function addProcessNotification()
    {
        $this->messageManager->addNotice(
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
