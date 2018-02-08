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
use Magento\Backend\App\Action;
use LizardMedia\VarnishWarmer\Helper\CacheCleaner;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Message\ManagerInterface;

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
     * @var CacheCleaner
     */
    protected $cacheCleaner;

    /**
     * @var QueueProgressLoggerInterface
     */
    protected $queueProgressLogger;

    /**
     * @var ProgressBarRendererInterface
     */
    protected $queueProgressBarRenderer;

    /**
     * PurgeAll constructor.
     * @param Context $context
     * @param DirectoryList $directoryList
     * @param CacheCleaner $cacheCleaner
     * @param QueueProgressLoggerInterface $queueProgressLogger
     * @param ProgressBarRendererInterface $queueProgressBarRenderer
     * @param array $data
     */
    public function __construct(
        Context $context,
        DirectoryList $directoryList,
        CacheCleaner $cacheCleaner,
        QueueProgressLoggerInterface $queueProgressLogger,
        ProgressBarRendererInterface $queueProgressBarRenderer,
        array $data = []
    ) {
        parent::__construct($context);
        $this->messageManager = $context->getMessageManager();
        $this->directoryList = $directoryList;
        $this->cacheCleaner = $cacheCleaner;
        $this->queueProgressLogger = $queueProgressLogger;
        $this->queueProgressBarRenderer = $queueProgressBarRenderer;
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
        $this->messageManager
            ->addWarningMessage(
                sprintf(
                    __(
                        'LizardMedia: cache is already being purged in background '
                        . '(started at: %s), '
                        . 'cannot run again until it finishes. <br />%s'
                    ),
                    $this->cacheCleaner->getLockMessage(),
                    $this->queueProgressBarRenderer->getProgressHtml($this->queueProgressLogger->getProgressData())
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
        return $this->cacheCleaner->isLocked();
    }

    /**
     * @return string
     */
    protected function getAdditionalParams(): string
    {
        return '';
    }
}
