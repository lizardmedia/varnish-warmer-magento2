<?php
/**
 * File: PurgeAll.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2019 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Controller\Adminhtml\Purge;

use LizardMedia\VarnishWarmer\Console\Command\PurgeAllCommand;
use LizardMedia\VarnishWarmer\Controller\Adminhtml\Purge;

/**
 * Class PurgeAll
 * @package LizardMedia\VarnishWarmer\Controller\Adminhtml\Purge
 */
class PurgeAll extends Purge
{
    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        if (!$this->isLocked()) {
            $this->runCommand();
            $this->addProcessNotification();
        } else {
            $this->addProcessLockWarning();
        }

        return $this->getRedirect();
    }

    /**
     * @return string
     */
    protected function getCliCommand(): string
    {
        return PurgeAllCommand::CLI_COMMAND;
    }
}
