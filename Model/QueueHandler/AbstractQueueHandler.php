<?php
/**
 * File: AbstractQueueHandler.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2018 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Model\QueueHandler;

use cURL\RequestsQueue;
use LizardMedia\VarnishWarmer\Api\Config\GeneralConfigProviderInterface;
use LizardMedia\VarnishWarmer\Api\ProgressHandler\QueueProgressLoggerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class AbstractQueueHandler
 * @package LizardMedia\VarnishWarmer\Model\QueueHandler
 */
abstract class AbstractQueueHandler
{
    const CURL_TIMEOUT = 30;

    /**
     * @var GeneralConfigProviderInterface
     */
    protected $configProvider;

    /**
     * @var RequestsQueue
     */
    protected $queue;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var QueueProgressLoggerInterface
     */
    protected $queueProgressLogger;

    /**
     * @var int
     */
    protected $counter = 0;

    /**
     * @var int
     */
    protected $total = 0;

    /**
     * @var array
     */
    protected $requests = [];

    /**
     * VarnishUrlRegenerator constructor.
     * @param GeneralConfigProviderInterface $configProvider
     * @param LoggerInterface $logger
     * @param QueueProgressLoggerInterface $queueProgressLogger
     */
    public function __construct(
        GeneralConfigProviderInterface $configProvider,
        LoggerInterface $logger,
        QueueProgressLoggerInterface $queueProgressLogger
    ) {
        $this->queue = new RequestsQueue();
        $this->configProvider = $configProvider;
        $this->logger = $logger;
        $this->queueProgressLogger = $queueProgressLogger;
    }

    /**
     * @return int
     */
    abstract protected function getMaxNumberOfProcesses(): int;

    /**
     * @return string
     */
    abstract protected function getQueueProcessType(): string;

    /**
     * @param string $url
     * @return null
     */
    protected function log(string $url)
    {
        $this->logger->debug("{$this->counter}/{$this->total}", ['url' => $url]);
    }

    /**
     * @return int
     */
    protected function getNumberOfParallelProcesses(): int
    {
        return min($this->getMaxNumberOfProcesses(), count($this->requests));
    }

    /**
     * @return null
     */
    protected function logProgress()
    {
        $this->queueProgressLogger->logProgress($this->getQueueProcessType(), $this->counter, $this->total);
    }
}
