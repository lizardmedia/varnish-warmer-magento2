<?php
/**
 * File: AbstractQueueHandler.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2018 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Model\QueueHandler;

use LizardMedia\VarnishWarmer\Api\Config\GeneralConfigProviderInterface;
use LizardMedia\VarnishWarmer\Api\ProgressHandler\QueueProgressLoggerInterface;
use LizardMedia\VarnishWarmer\Model\Adapter\ReactPHP\ClientFactory;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\Factory;

/**
 * Class AbstractQueueHandler
 * @package LizardMedia\VarnishWarmer\Model\QueueHandler
 */
abstract class AbstractQueueHandler
{
    /**
     * @var GeneralConfigProviderInterface
     */
    protected $configProvider;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var QueueProgressLoggerInterface
     */
    protected $queueProgressLogger;

    /**
     * @var LoopInterface
     */
    protected $loop;

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
    protected $urls = [];

    /**
     * @var ClientFactory
     */
    protected $clientFactory;

    /**
     * AbstractQueueHandler constructor.
     * @param GeneralConfigProviderInterface $configProvider
     * @param LoggerInterface $logger
     * @param QueueProgressLoggerInterface $queueProgressLogger
     * @param Factory $loopFactory
     * @param ClientFactory $clientFactory
     */
    public function __construct(
        GeneralConfigProviderInterface $configProvider,
        LoggerInterface $logger,
        QueueProgressLoggerInterface $queueProgressLogger,
        Factory $loopFactory,
        ClientFactory $clientFactory
    ) {
        $this->configProvider = $configProvider;
        $this->logger = $logger;
        $this->queueProgressLogger = $queueProgressLogger;
        $this->loop = $loopFactory::create();
        $this->clientFactory = $clientFactory;
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
        return min($this->getMaxNumberOfProcesses(), count($this->urls));
    }

    /**
     * @return null
     */
    protected function logProgress()
    {
        $this->queueProgressLogger->logProgress($this->getQueueProcessType(), $this->counter, $this->total);
    }
}
