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
use GuzzleHttp\Psr7\Request;
use LizardMedia\VarnishWarmer\Api\QueueHandler\VarnishUrlPurgerInterface;
use LizardMedia\VarnishWarmer\Model\Adapter\Guzzle\ClientFactory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

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
     * @param ClientFactory $clientFactory
     * @param PurgingConfigProviderInterface $purgingConfigProvider
     */
    public function __construct(
        GeneralConfigProviderInterface $configProvider,
        LoggerInterface $logger,
        QueueProgressLoggerInterface $queueProgressLogger,
        ClientFactory $clientFactory,
        PurgingConfigProviderInterface $purgingConfigProvider
    ) {
        parent::__construct($configProvider, $logger, $queueProgressLogger, $clientFactory);
        $this->purgingConfigProvider = $purgingConfigProvider;
    }

    /**
     * @param string $url
     * @return void
     */
    public function addUrlToPurge(string $url): void
    {
        $this->queue(new Request('PURGE', $url, $this->buildHeaders()));
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

    protected function handleResponse(RequestInterface $request, ResponseInterface $response): void
    {
        $this->counter++;
        $this->log($request, $response, $this->getResponseHeaders($response));
        $this->logProgress();
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
