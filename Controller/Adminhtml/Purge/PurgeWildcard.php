<?php
/**
 * File: PurgeWildcard.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2018 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Controller\Adminhtml\Purge;

use LizardMedia\VarnishWarmer\Console\Command\PurgeWildcardCommand;
use LizardMedia\VarnishWarmer\Controller\Adminhtml\Purge;

/**
 * Class PurgeWildcard
 * @package LizardMedia\VarnishWarmer\Controller\Adminhtml\Purge
 */
class PurgeWildcard extends Purge
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
        return PurgeWildcardCommand::CLI_COMMAND;
    }
}
