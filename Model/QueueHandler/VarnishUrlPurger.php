<?php
/**
 * File: VarnishUrlPurger.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2019 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Model\QueueHandler;

use LizardMedia\VarnishWarmer\Api\Config\GeneralConfigProviderInterface;
use LizardMedia\VarnishWarmer\Api\Config\PurgingConfigProviderInterface;
use LizardMedia\VarnishWarmer\Api\ProgressHandler\QueueProgressLoggerInterface;
use LizardMedia\VarnishWarmer\Api\QueueHandler\VarnishUrlPurgerInterface;
use LizardMedia\VarnishWarmer\Model\Adapter\ReactPHP\ClientFactory;
use Psr\Log\LoggerInterface;
use React\HttpClient\Response;
use React\EventLoop\Factory;

/**
 * Class VarnishUrlPurger
 * @package LizardMedia\VarnishWarmer\Model\QueueHandler
 */
class VarnishUrlPurger extends AbstractQueueHandler implements VarnishUrlPurgerInterface
{
    /**
     * @var string
     */
    const CURL_CUSTOMREQUEST = 'PURGE';

    /**
     * @var string
     */
    const PROCESS_TYPE = 'PURGE';

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
    public function runPurgeQueue(): void
    {
        while (!empty($this->urls)) {
            for($i = 0; $i < $this->getMaxNumberOfProcesses(); $i++) {
                if (!empty($this->urls)) {
                    $this->createRequest(array_pop($this->urls));
                }
            }
            $this->loop->run();
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
     *
     * @param string $url
     * @return void
     */
    private function createRequest(string $url): void
    {
        $client = $this->clientFactory->create($this->loop);
        $request = $client->request('GET', $url, $this->buildHeaders());
        $request->on('response', function (Response $response) use ($url) {
            $response->on(
                'end',
                function () use ($url){
                    $this->counter++;
                    $this->log($url);
                    $this->logProgress();
                }
            );
        });
        $request->end();
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
