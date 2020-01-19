<?php

declare(strict_types=1);

/**
 * File: VarnishUrlRegenerator.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @author Bartosz Kubicki <bartosz.kubicki@lizardmedia.pl>
 * @copyright Copyright (C) 2020 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Model\QueueHandler;

use Exception;
use LizardMedia\VarnishWarmer\Api\QueueHandler\VarnishUrlRegeneratorInterface;
use React\HttpClient\Response;
use RuntimeException;

/**
 * Class VarnishUrlRegenerator
 * @package LizardMedia\VarnishWarmer\Model\QueueHandler
 */
class VarnishUrlRegenerator extends AbstractQueueHandler implements VarnishUrlRegeneratorInterface
{
    /**
     * @var string
     */
    private const PROCESS_TYPE = 'REGENERATE';

    /**
     * @param string $url
     * @return void
     */
    public function addUrlToRegenerate(string $url): void
    {
        $this->urls[] = $url;
        $this->total++;
    }

    /**
     * @return void
     */
    public function regenerate(): void
    {
        $this->runQueue();
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

    /**
     * @param string $url
     * @return void
     */
    protected function createRequest(string $url): void
    {
        $client = $this->clientFactory->create($this->loop);
        $request = $client->request('GET', $url);

        $request->on('error', function (RuntimeException $exception) use ($url) {
            $this->logger->error($exception->getMessage(), [$url]);
        });

        $request->on('response', function (Response $response) use ($url) {
            $response->on(
                'end',
                function () use ($url){
                    $this->counter++;
                    $this->log($url);
                    $this->logProgress();
                }
            );

            $response->on('error', function (Exception $exception) use ($url) {
                $this->logger->error(
                    $exception->getMessage(),
                    [
                        'url' => $url
                    ]
                );
            });
        });

        $request->end();
    }
}
