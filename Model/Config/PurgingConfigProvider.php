<?php
/**
 * File: PurgingConfigProvider.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2019 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Model\Config;

use LizardMedia\VarnishWarmer\Api\Config\PurgingConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class PurgingConfigProvider
 * @package LizardMedia\VarnishWarmer\Api\Config
 */
class PurgingConfigProvider implements PurgingConfigProviderInterface
{
    /**
     * @var string
     */
    const XML_PATH_USE_CUSTOM_HOST = 'lm_varnish/purge/different_purge_host';
    const XML_PATH_CUSTOM_HOST = 'lm_varnish/purge/custom_host';
    const XML_PATH_CUSTOM_HEADER_HOST = 'lm_varnish/purge/header_host';

    /**
     * @var string
     */
    const PURGE_HOSTS_DELIMITER = ',';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * GeneralConfigProvider constructor.
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return bool
     */
    public function isPurgeCustomHostEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_USE_CUSTOM_HOST);
    }

    /**
     * @return array
     */
    public function getCustomPurgeHosts(): array
    {
        $hostsFieldValue = (string)$this->scopeConfig->getValue(self::XML_PATH_CUSTOM_HOST);
        return explode(self::PURGE_HOSTS_DELIMITER, $hostsFieldValue);

    }

    /**
     * @return string
     */
    public function getAdditionalHostForHeader(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_CUSTOM_HEADER_HOST);
    }
}
