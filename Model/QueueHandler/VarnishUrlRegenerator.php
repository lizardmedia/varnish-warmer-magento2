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

use GuzzleHttp\Psr7\Request;
use LizardMedia\VarnishWarmer\Api\Config\GeneralConfigProviderInterface;
use LizardMedia\VarnishWarmer\Api\ProgressHandler\QueueProgressLoggerInterface;
use LizardMedia\VarnishWarmer\Api\QueueHandler\VarnishUrlRegeneratorInterface;
use LizardMedia\VarnishWarmer\Model\Adapter\Guzzle\ClientFactory;
use LizardMedia\VarnishWarmer\Model\HttpContext\VaryCookieProvider;
use Magento\Framework\App\Response\Http as HttpResponse;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

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

    private VaryCookieProvider $varyCookieProvider;

    public function __construct(
        GeneralConfigProviderInterface $configProvider,
        LoggerInterface $logger,
        QueueProgressLoggerInterface $queueProgressLogger,
        ClientFactory $clientFactory,
        VaryCookieProvider $varyCookieProvider
    ) {
        parent::__construct($configProvider, $logger, $queueProgressLogger, $clientFactory);
        $this->varyCookieProvider = $varyCookieProvider;
    }

    public function addUrlToRegenerate(string $url, ?int $storeId = null): void
    {
        $varyCookies = $storeId === null ? [null] : $this->varyCookieProvider->getVaryCookies($storeId);

        foreach ($varyCookies as $varyCookie) {
            $headers = $varyCookie === null ? [] : ['Cookie' => HttpResponse::COOKIE_VARY_STRING . '=' . $varyCookie];
            $this->queue(new Request('GET', $url, $headers));
        }
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

    protected function handleResponse(RequestInterface $request, ResponseInterface $response): void
    {
        $this->counter++;
        $this->log($request, $response);
        $this->logProgress();
    }
}
