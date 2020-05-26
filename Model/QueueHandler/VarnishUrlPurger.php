<?php

declare(strict_types=1);

/**
 * File: VarnishUrlPurger.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @author Bartosz Kubicki <bartosz.kubicki@lizardmedia.pl>
 * @copyright Copyright (C) 2020 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Model\QueueHandler;

use LizardMedia\VarnishWarmer\Api\Config\GeneralConfigProviderInterface;
use LizardMedia\VarnishWarmer\Api\Config\PurgingConfigProviderInterface;
use LizardMedia\VarnishWarmer\Api\ProgressHandler\QueueProgressLoggerInterface;
use LizardMedia\VarnishWarmer\Api\QueueHandler\VarnishUrlPurgerInterface;
use LizardMedia\VarnishWarmer\Model\Adapter\ReactPHP\ClientFactory;
use Psr\Log\LoggerInterface;
use React\EventLoop\Factory;
use React\HttpClient\Response;
use RuntimeException;

/**
 * Class VarnishUrlPurger
 * @package LizardMedia\VarnishWarmer\Model\QueueHandler
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class VarnishUrlPurger extends AbstractQueueHandler implements VarnishUrlPurgerInterface
{
    /**
     * @var string
     */
    private const PROCESS_TYPE = 'PURGE';

    /**
     * @var PurgingConfigProviderInterface
     */
    private $purgingConfigProvider;

    /**
     * VarnishUrlPurger constructor.
     * @param GeneralConfigProviderInterface $configProvider
     * @param LoggerInterface $logger
     * @param QueueProgressLoggerInterface $queueProgressLogger
     * @param Factory $loopFactory
     * @param ClientFactory $clientFactory
     * @param PurgingConfigProviderInterface $purgingConfigProvider
     */
    public function __construct(
        GeneralConfigProviderInterface $configProvider,
        LoggerInterface $logger,
        QueueProgressLoggerInterface $queueProgressLogger,
        Factory $loopFactory,
        ClientFactory $clientFactory,
        PurgingConfigProviderInterface $purgingConfigProvider
    ) {
        parent::__construct($configProvider, $logger, $queueProgressLogger, $loopFactory, $clientFactory);
        $this->purgingConfigProvider = $purgingConfigProvider;
    }

    /**
     * @param string $url
     * @return void
     */
    public function addUrlToPurge(string $url): void
    {
        $this->urls[] = $url;
        $this->total++;
    }

    /**
     * @return void
     */
    public function purge(): void
    {
        $this->runQueue();
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
     *
     * @param string $url
     * @return void
     */
    protected function createRequest(string $url): void
    {
        $client = $this->clientFactory->create($this->loop);
        $request = $client->request('PURGE', $url, $this->buildHeaders());

        $request->on('error', function (RuntimeException $exception) use ($url) {
            $this->logger->error($exception->getMessage(), [$url]);
        });

        $request->on('response', function (Response $response) use ($url) {
            $responseCode = $response->getCode();
            $responseHeaders = $response->getHeaders();

            $response->on(
                'end',
                function () use ($url, $responseCode, $responseHeaders) {
                    $this->counter++;
                    $this->log($url, $responseCode, $responseHeaders);
                    $this->logProgress();
                }
            );

            $response->on('error', function (RuntimeException $exception) use ($url) {
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

    /**
     * @return array
     */
    private function buildHeaders(): array
    {
        $headers = [];
        $headers['X-Magento-Tags-Pattern'] = '.*';
        if ($this->purgingConfigProvider->isPurgeCustomHostEnabled()
            && $this->purgingConfigProvider->getAdditionalHostForHeader()) {
            $headers['Host'] = $this->purgingConfigProvider->getAdditionalHostForHeader();
        }
        return $headers;
    }
}
