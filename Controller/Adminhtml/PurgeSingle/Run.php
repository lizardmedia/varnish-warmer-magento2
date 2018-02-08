<?php
/**
 * File: Run.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2018 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Controller\Adminhtml\PurgeSingle;

use LizardMedia\VarnishWarmer\Block\Adminhtml\PurgeSingle\Form\Edit\Form;
use LizardMedia\VarnishWarmer\Console\Command\PurgeUrlCommand;
use LizardMedia\VarnishWarmer\Controller\Adminhtml\Purge;
use Magento\Framework\App\ResponseInterface;

/**
 * Class Run
 * @package LizardMedia\VarnishWarmer\Controller\Adminhtml\Form
 */
class Run extends Purge
{
    /**
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
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
        return PurgeUrlCommand::CLI_COMMAND;
    }

    /**
     * @return string
     */
    protected function getAdditionalParams(): string
    {
        $url = (string)$this->getRequest()->getParam(Form::URL_FORM_PARAM);
        $additionalParams = " \"{$url}\"";
        $additionalParams .= parent::getAdditionalParams();
        return $additionalParams;
    }
}
