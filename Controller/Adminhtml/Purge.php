<?php

declare(strict_types=1);

/**
 * File: Purge.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2019 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Controller\Adminhtml;

use LizardMedia\VarnishWarmer\Api\ProgressHandler\ProgressBarRendererInterface;
use LizardMedia\VarnishWarmer\Api\ProgressHandler\QueueProgressLoggerInterface;
use LizardMedia\VarnishWarmer\Api\VarnishActionManagerInterface;
use LizardMedia\VarnishWarmer\Block\Adminhtml\PurgeSingle\Form\Edit\Form;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Message\Factory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Message\MessageInterface;

/**
 * Class Purge
 * @package LizardMedia\VarnishWarmer\Controller\Adminhtml
 * @SuppressWarnings(PHPMD.LongVariable)
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
     * @var VarnishActionManagerInterface
     */
    protected $varnishActionManager;

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
     * @param VarnishActionManagerInterface $varnishActionManager
     * @param QueueProgressLoggerInterface $queueProgressLogger
     * @param ProgressBarRendererInterface $queueProgressBarRenderer
     * @param Factory $messageFactory
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function __construct(
        Context $context,
        DirectoryList $directoryList,
        VarnishActionManagerInterface $varnishActionManager,
        QueueProgressLoggerInterface $queueProgressLogger,
        ProgressBarRendererInterface $queueProgressBarRenderer,
        Factory $messageFactory
    ) {
        parent::__construct($context);
        $this->messageManager = $context->getMessageManager();
        $this->directoryList = $directoryList;
        $this->varnishActionManager = $varnishActionManager;
        $this->queueProgressLogger = $queueProgressLogger;
        $this->queueProgressBarRenderer = $queueProgressBarRenderer;
        $this->messageFactory = $messageFactory;
    }

    /**
     * @return Redirect
     */
    public function execute(): Redirect
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
     * @return void
     */
    protected function addProcessNotification(): void
    {
        $this->messageManager->addNoticeMessage(__('LizardMedia: cache is purged in background, it may take a while.'));
    }

    /**
     * @return void
     */
    protected function addProcessLockWarning(): void
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
                    $this->varnishActionManager->getLockMessage(),
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
        $additionalParams = $this->getAdditionalParams();
        $baseDir = $this->directoryList->getRoot();
        $cliCommand = $this->getCliCommand();
        $cmd = "nohup {$baseDir}/bin/magento {$cliCommand}{$additionalParams}>/dev/null 2>&1 &";
        exec($cmd);
    }

    /**
     * @return bool
     */
    protected function isLocked(): bool
    {
        return $this->varnishActionManager->isLocked();
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
