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

use Generator;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\Each;
use GuzzleHttp\Promise\PromiseInterface;
use LizardMedia\VarnishWarmer\Api\Config\GeneralConfigProviderInterface;
use LizardMedia\VarnishWarmer\Api\ProgressHandler\QueueProgressLoggerInterface;
use LizardMedia\VarnishWarmer\Model\Adapter\Guzzle\ClientFactory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Throwable;

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
     * @var int
     */
    protected $counter = 0;

    /**
     * @var int
     */
    protected $total = 0;

    private array $requests = [];

    private ClientInterface $client;

    /**
     * @param GeneralConfigProviderInterface $configProvider
     * @param LoggerInterface $logger
     * @param QueueProgressLoggerInterface $queueProgressLogger
     * @param ClientFactory $clientFactory
     */
    public function __construct(
        GeneralConfigProviderInterface $configProvider,
        LoggerInterface $logger,
        QueueProgressLoggerInterface $queueProgressLogger,
        ClientFactory $clientFactory
    ) {
        $this->configProvider = $configProvider;
        $this->logger = $logger;
        $this->queueProgressLogger = $queueProgressLogger;
        $this->client = $clientFactory->create();
    }

    abstract protected function getMaxNumberOfProcesses(): int;

    abstract protected function getQueueProcessType(): string;

    abstract protected function handleResponse(RequestInterface $request, ResponseInterface $response): void;

    protected function queue(RequestInterface $request): void
    {
        $this->requests[] = $request;
        $this->total++;
    }

    protected function runQueue(): void
    {
        Each::ofLimit($this->sendRequests(), max(1, $this->getMaxNumberOfProcesses()))->wait();
    }

    protected function log(RequestInterface $request, ResponseInterface $response, array $responseHeaders = []): void
    {
        unset($responseHeaders['Set-Cookie']);
        $context = [
            'url' => (string) $request->getUri(),
            'code' => $response->getStatusCode(),
            'headers' => implode(',', $responseHeaders),
        ];
        if ($request->hasHeader('Cookie')) {
            $context['cookie'] = $request->getHeaderLine('Cookie');
        }

        $this->logger->debug("{$this->counter}/{$this->total}", $context);
    }

    protected function getResponseHeaders(ResponseInterface $response): array
    {
        return array_map(
            static fn (array $values): string => implode(', ', $values),
            $response->getHeaders()
        );
    }

    protected function logProgress(): void
    {
        $this->queueProgressLogger->logProgress($this->getQueueProcessType(), $this->counter, $this->total);
    }

    /**
     * @return Generator<PromiseInterface>
     */
    private function sendRequests(): Generator
    {
        while (!empty($this->requests)) {
            $request = array_pop($this->requests);

            yield $this->client->sendAsync($request)->then(
                function (ResponseInterface $response) use ($request): void {
                    $this->handleResponse($request, $response);
                },
                function (Throwable $exception) use ($request): void {
                    $this->logger->error($exception->getMessage(), ['url' => (string) $request->getUri()]);
                }
            );
        }
    }
}
