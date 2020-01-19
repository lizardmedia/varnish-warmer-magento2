<?php

declare(strict_types=1);

/**
 * File: AbstractQueueHandler.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @author Bartosz Kubicki <bartosz.kubicki@lizardmedia.pl>
 * @copyright Copyright (C) 2019 Lizard Media (http://lizardmedia.pl)
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
     * @return void
     */
    abstract protected function createRequest(string $url): void;

    /**
     * @return void
     */
    protected function runQueue(): void
    {
        while (!empty($this->urls)) {
            for ($i = 0; $i < $this->getMaxNumberOfProcesses(); $i++) {
                if (!empty($this->urls)) {
                    $this->createRequest(array_pop($this->urls));
                }
            }

            $this->loop->run();
        }
    }

    /**
     * @param string $url
     * @return void
     */
    protected function log(string $url): void
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
     * @return void
     */
    protected function logProgress(): void
    {
        $this->queueProgressLogger->logProgress($this->getQueueProcessType(), $this->counter, $this->total);
    }
}
