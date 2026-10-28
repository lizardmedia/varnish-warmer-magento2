<?php

declare(strict_types=1);

/**
 * @author Paweł Majcherczyk <pawel.majcherczyk@lizardmedia.pl>
 * @copyright Copyright (C) 2026 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Model\Adapter\Guzzle;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use LizardMedia\VarnishWarmer\Api\Config\GeneralConfigProviderInterface;

class ClientFactory
{
    private GeneralConfigProviderInterface $configProvider;
    private float $connectTimeoutSeconds;
    private float $timeoutSeconds;

    public function __construct(
        GeneralConfigProviderInterface $configProvider,
        float $connectTimeoutSeconds = 10.0,
        float $timeoutSeconds = 60.0
    ) {
        $this->configProvider = $configProvider;
        $this->connectTimeoutSeconds = $connectTimeoutSeconds;
        $this->timeoutSeconds = $timeoutSeconds;
    }

    public function create(): ClientInterface
    {
        $options = [
            RequestOptions::HTTP_ERRORS => false,
            RequestOptions::ALLOW_REDIRECTS => false,
            RequestOptions::CONNECT_TIMEOUT => $this->connectTimeoutSeconds,
            RequestOptions::TIMEOUT => $this->timeoutSeconds,
        ];

        $httpAuthUsername = $this->configProvider->getHttpAuthUsername();
        if ($httpAuthUsername !== '') {
            $options[RequestOptions::AUTH] = [$httpAuthUsername, $this->configProvider->getHttpAuthPassword()];
        }

        return new Client($options);
    }
}
