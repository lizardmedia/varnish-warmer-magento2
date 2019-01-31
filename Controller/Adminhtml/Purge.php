<?php
/**
 * File: Purge.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2018 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Controller\Adminhtml;

use LizardMedia\VarnishWarmer\Api\ProgressHandler\ProgressBarRendererInterface;
use LizardMedia\VarnishWarmer\Api\ProgressHandler\QueueProgressLoggerInterface;
use LizardMedia\VarnishWarmer\Api\VarnishPurgerInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Message\ManagerInterface;
use LizardMedia\VarnishWarmer\Block\Adminhtml\PurgeSingle\Form\Edit\Form;
use Magento\Framework\Message\Factory;
use Magento\Framework\Message\MessageInterface;

/**
 * Class Purge
 * @package LizardMedia\VarnishWarmer\Controller\Adminhtml
 */
abstract class Purge extends Action
{
    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var VarnishPurgerInterface
     */
    protected $varnishPurger;

    /**
     * @var QueueProgressLoggerInterface
     */
    protected $queueProgressLogger;

    /**
     * @var ProgressBarRendererInterface
     */
    protected $queueProgressBarRenderer;

    /**
     * @var Factory
     */
    private $messageFactory;

    /**
     * Purge constructor.
     * @param Context $context
     * @param DirectoryList $directoryList
     * @param VarnishPurgerInterface $varnishPurger
     * @param QueueProgressLoggerInterface $queueProgressLogger
     * @param ProgressBarRendererInterface $queueProgressBarRenderer
     * @param Factory $messageFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        DirectoryList $directoryList,
        VarnishPurgerInterface $varnishPurger,
        QueueProgressLoggerInterface $queueProgressLogger,
        ProgressBarRendererInterface $queueProgressBarRenderer,
        Factory $messageFactory,
        array $data = []
    ) {
        parent::__construct($context);
        $this->messageManager = $context->getMessageManager();
        $this->directoryList = $directoryList;
        $this->varnishPurger = $varnishPurger;
        $this->queueProgressLogger = $queueProgressLogger;
        $this->queueProgressBarRenderer = $queueProgressBarRenderer;
        $this->messageFactory = $messageFactory;
    }

    /**
     * @return string
     */
    abstract protected function getCliCommand(): string;

    /**
     * @return Redirect
     */
    protected function getRedirect(): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('admin/dashboard/index');
        return $resultRedirect;
    }

    /**
     * @return null
     */
    protected function addProcessNotification()
    {
        $this->messageManager->addNoticeMessage(__('LizardMedia: cache is purged in background, it may take a while.'));
    }

    /**
     * @return null
     */
    protected function addProcessLockWarning()
    {
        $this->messageManager->addMessage(
            $this->messageFactory->create(
                MessageInterface::TYPE_WARNING,
                sprintf(
                    __(
                        'LizardMedia: cache is already being purged in background '
                        . '(started at: %s), '
                        . 'cannot run again until it finishes. <br />%s'
                    ),
                    $this->varnishPurger->getLockMessage(),
                    $this->queueProgressBarRenderer->getProgressHtml($this->queueProgressLogger->getProgressData())
                )
            )
        );
    }

    /**
     * @return void
     */
    protected function runCommand(): void
    {
        $additional_params = $this->getAdditionalParams();
        $baseDir = $this->directoryList->getRoot();
        $cliCommand = $this->getCliCommand();
        $cmd = "nohup {$baseDir}/bin/magento {$cliCommand}{$additional_params}>/dev/null 2>&1 &";
        exec($cmd);
    }

    /**
     * @return bool
     */
    protected function isLocked(): bool
    {
        return $this->varnishPurger->isLocked();
    }

    /**
     * @return string
     */
    protected function getAdditionalParams(): string
    {
        $additionalParams = '';
        if ($this->getRequest()->getParam(Form::STORE_VIEW_FORM_PARAM)) {
            $additionalParams .= ' --store=' . $this->getRequest()->getParam(Form::STORE_VIEW_FORM_PARAM);
        }
        return $additionalParams;
    }
}
