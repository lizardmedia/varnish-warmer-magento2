<?php
/**
 * File: FileQueueProgressLogger.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2018 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Model\ProgressHandler;

use LizardMedia\VarnishWarmer\Api\ProgressHandler\ProgressDataInterface;
use LizardMedia\VarnishWarmer\Api\ProgressHandler\QueueProgressLoggerInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File;
use Zend\Json\Json;

/**
 * Class FileQueueProgressLogger
 * @package LizardMedia\VarnishWarmer\Model\ProgressHandler
 */
class FileQueueProgressLogger implements QueueProgressLoggerInterface
{
    const LOG_DIR = '/var/log/varnish/';
    const LOG_FILE = '.queue_progress.log';

    /**
     * @var File
     */
    private $fileHandler;

    /**
     * @var string
     */
    private $logDir;

    /**
     * @var string
     */
    private $logFile;

    /**
     * @var ProgressDataFactory
     */
    private $progressDataFactory;

    /**
     * FileLock constructor.
     * @param DirectoryList $directoryList
     * @param File $fileHandler
     * @param ProgressDataFactory $progressDataFactory
     */
    public function __construct(
        DirectoryList $directoryList,
        File $fileHandler,
        ProgressDataFactory $progressDataFactory
    ) {
        $this->logDir = $directoryList->getRoot() . self::LOG_DIR;
        $this->logFile = $directoryList->getRoot() . self::LOG_DIR . self::LOG_FILE;
        $this->fileHandler = $fileHandler;
        $this->progressDataFactory = $progressDataFactory;
    }

    /**
     * @param string $type
     * @param int $current
     * @param int $total
     * @return void
     */
    public function logProgress(string $type, int $current, int $total): void
    {
        $this->fileHandler->checkAndCreateFolder($this->logDir);
        $this->fileHandler->write(
            $this->logFile,
            $this->prepareDataToLog($type, $current, $total)
        );
    }

    /**
     * @return ProgressDataInterface
     */
    public function getProgressData(): ProgressDataInterface
    {
        $loggedData = $this->fileHandler->read($this->logFile);
        return $this->retrieveLogData($loggedData);
    }

    /**
     * @param string $type
     * @param int $current
     * @param int $total
     * @return string
     */
    private function prepareDataToLog(string $type, int $current, int $total): string
    {
        return Json::encode(
            [
                ProgressDataInterface::FIELD_PROCESS_TYPE => $type,
                ProgressDataInterface::FIELD_CURRENT => $current,
                ProgressDataInterface::FIELD_TOTAL => $total
            ]
        );
    }

    /**
     * @param string $loggedData
     * @return ProgressDataInterface
     */
    private function retrieveLogData(string $loggedData): ProgressDataInterface
    {
        /** @var ProgressData $progressData */
        $progressData = $this->progressDataFactory->create();
        try {
            $loggedDataArray = Json::decode($loggedData, Json::TYPE_ARRAY);
            $progressData->addData($loggedDataArray);
        } catch (\Exception $e) {
        }
        return $progressData;
    }
}
