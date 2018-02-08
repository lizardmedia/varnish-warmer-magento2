<?php
/**
 * File: VarnishUrlRegenerator.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2018 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Model\QueueHandler;

use cURL\Event;
use cURL\Request;
use LizardMedia\VarnishWarmer\Api\QueueHandler\VarnishUrlRegeneratorInterface;

/**
 * Class VarnishUrlRegenerator
 * @package LizardMedia\VarnishWarmer\Model\QueueHandler
 */
class VarnishUrlRegenerator extends AbstractQueueHandler implements VarnishUrlRegeneratorInterface
{
    const PROCESS_TYPE = 'REGENERATE';

    /**
     * @param string $url
     * @return void
     */
    public function addUrlToRegenerate(string $url): void
    {
        $this->requests[] = new Request($url);
        $this->total++;
    }

    /**
     * @return void
     */
    public function runRegenerationQueue(): void
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
        return $this->configProvider->getMaxConcurrentRegenerationProcesses();
    }

    /**
     * @return string
     */
    protected function getQueueProcessType(): string
    {
        return self::PROCESS_TYPE;
    }
}
