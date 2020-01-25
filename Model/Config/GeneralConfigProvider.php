<?php

declare(strict_types=1);

/**
 * File: GeneralConfigProvider.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2019 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Model\Config;

use LizardMedia\VarnishWarmer\Api\Config\GeneralConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class GeneralConfigProvider
 * @package LizardMedia\VarnishWarmer\Api\Config
 */
class GeneralConfigProvider implements GeneralConfigProviderInterface
{
    /**
     * @var string
     */
    private const XML_PATH_CONCURRENT_REGENERATION = 'lm_varnish/general/max_concurrent_regeneration';
    private const XML_PATH_CONCURRENT_PURGE = 'lm_varnish/general/max_concurrent_purge';

    /**
     * @var int
     */
    private const REGENERATION_PROCESSES_DEFAULT = 10;
    private const REGENERATION_PROCESSES_MAX = 20;
    private const REGENERATION_PROCESSES_MIN = 1;

    /**
     * @var int
     */
    private const PURGE_PROCESSES_DEFAULT = 4;
    private const PURGE_PROCESSES_MAX = 20;
    private const PURGE_PROCESSES_MIN = 1;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * GeneralConfigProvider constructor.
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return int
     */
    public function getMaxConcurrentRegenerationProcesses(): int
    {
        $configValue = (int) $this->scopeConfig->getValue(self::XML_PATH_CONCURRENT_REGENERATION);
        return $this->isMaxRegenerationProcessesConfigValid($configValue)
            ? $configValue
            : self::REGENERATION_PROCESSES_DEFAULT;
    }

    /**
     * @return int
     */
    public function getMaxConcurrentPurgeProcesses(): int
    {
        $configValue = (int) $this->scopeConfig->getValue(self::XML_PATH_CONCURRENT_PURGE);
        return $this->isMaxPurgeProcessesConfigValid($configValue)
            ? $configValue
            : self::PURGE_PROCESSES_DEFAULT;
    }

    /**
     * @param int $configValue
     * @return bool
     */
    protected function isMaxRegenerationProcessesConfigValid(int $configValue): bool
    {
        return $configValue >= self::REGENERATION_PROCESSES_MIN && $configValue <= self::REGENERATION_PROCESSES_MAX;
    }

    /**
     * @param int $configValue
     * @return bool
     */
    protected function isMaxPurgeProcessesConfigValid(int $configValue): bool
    {
        return $configValue >= self::PURGE_PROCESSES_MIN && $configValue <= self::PURGE_PROCESSES_MAX;
    }
}
