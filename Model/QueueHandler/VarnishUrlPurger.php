<?php
/**
 * File: VarnishUrlPurger.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2018 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Model\QueueHandler;

use LizardMedia\VarnishWarmer\Api\Config\GeneralConfigProviderInterface;
use LizardMedia\VarnishWarmer\Api\Config\PurgingConfigProviderInterface;
use LizardMedia\VarnishWarmer\Api\ProgressHandler\QueueProgressLoggerInterface;
use LizardMedia\VarnishWarmer\Api\QueueHandler\VarnishUrlPurgerInterface;
use cURL\Event;
use cURL\Request;
use cURL\RequestsQueue;
use Magento\Framework\App\Filesystem\DirectoryList;
use Psr\Log\LoggerInterface;

/**
 * Class VarnishUrlPurger
 * @package LizardMedia\VarnishWarmer\Model\QueueHandler
 */
class VarnishUrlPurger extends AbstractQueueHandler implements VarnishUrlPurgerInterface
{
    const CURL_CUSTOMREQUEST = 'PURGE';
    const PROCESS_TYPE = 'PURGE';

    /**
     * @var bool
     */
    private $verifyPeer = true;

    /**
     * @var PurgingConfigProviderInterface
     */
    private $purgingConfigProvider;

    /**
     * VarnishUrlPurger constructor.
     * @param GeneralConfigProviderInterface $configProvider
     * @param LoggerInterface $logger
     * @param QueueProgressLoggerInterface $queueProgressLogger
     * @param PurgingConfigProviderInterface $purgingConfigProvider
     */
    public function __construct(
        GeneralConfigProviderInterface $configProvider,
        LoggerInterface $logger,
        QueueProgressLoggerInterface $queueProgressLogger,
        PurgingConfigProviderInterface $purgingConfigProvider
    ) {
        parent::__construct($configProvider, $logger, $queueProgressLogger);
        $this->purgingConfigProvider = $purgingConfigProvider;
    }

    /**
     * @return bool
     */
    public function isVerifyPeer(): bool
    {
        return $this->verifyPeer;
    }

    /**
     * @param bool $verifyPeer
     * @return void
     */
    public function setVerifyPeer(bool $verifyPeer): void
    {
        $this->verifyPeer = $verifyPeer;
    }

    /**
     * @param string $url
     * @return void
     */
    public function addUrlToPurge(string $url): void
    {
        $this->requests[] = new Request($url);
        $this->total++;
    }

    /**
     * @return void
     */
    public function runPurgeQueue(): void
    {
        $this->buildQueue();
        $this->runQueueProcesses();
    }

    /**
     * @return void
     */
    protected function buildQueue(): void
    {
        $this->queue
            ->getDefaultOptions()
            ->set(CURLOPT_TIMEOUT, self::CURL_TIMEOUT)
            ->set(CURLOPT_CUSTOMREQUEST, self::CURL_CUSTOMREQUEST)
            ->set(CURLOPT_VERBOSE, false)
            ->set(CURLOPT_SSL_VERIFYPEER, $this->isVerifyPeer())
            ->set(CURLOPT_HTTPHEADER, $this->buildHeaders())
            ->set(CURLOPT_RETURNTRANSFER, true);

        $requests = &$this->requests;
        $this->queue->addListener('complete', function (Event $event) use (&$requests) {
            $this->counter++;
            $url = curl_getinfo($event->request->getHandle(), CURLINFO_EFFECTIVE_URL);
            $this->log($url);
            $this->logProgress();
            if ($next = array_pop($requests)) {
                $event->queue->attach($next);
            }
        });
    }

    /**
     * @return void
     */
    protected function runQueueProcesses(): void
    {
        $numberOfProcesses = $this->getNumberOfParallelProcesses();
        for ($i = 0; $i < $numberOfProcesses; $i++) {
            $this->queue->attach(array_pop($this->requests));
        }
        if ($this->queue->count() > 0) {
            $this->queue->send();
        }
    }

    /**
     * @return int
     */
    protected function getMaxNumberOfProcesses(): int
    {
        return $this->configProvider->getMaxConcurrentPurgeProcesses();
    }

    /**
     * @return string
     */
    protected function getQueueProcessType(): string
    {
        return self::PROCESS_TYPE;
    }

    /**
     * @return array
     */
    private function buildHeaders(): array
    {
        $headers = [];
        $headers[] = 'X-Magento-Tags-Pattern: .*';
        if ($this->purgingConfigProvider->isPurgeCustomHostEnabled()
            && $this->purgingConfigProvider->getAdditionalHostForHeader()) {
            $headers[] = "Host: {$this->purgingConfigProvider->getAdditionalHostForHeader()}";
        }
        return $headers;
    }
}
