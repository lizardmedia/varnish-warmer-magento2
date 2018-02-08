<?php
/**
 * File: PurgeWithoutRegenNotificationMailerConfigProvider.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2018 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Model\Config;

use LizardMedia\VarnishWarmer\Api\Config\PurgeWithoutRegenNotificationMailerConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class PurgeWithoutRegenNotificationMailerConfigProvider
 * @package LizardMedia\VarnishWarmer\Model\Config
 */
class PurgeWithoutRegenNotificationMailerConfigProvider implements
    PurgeWithoutRegenNotificationMailerConfigProviderInterface
{
    const XML_PATH_EMAIL_TO = 'lm_varnish_cache/purge_without_regen/email_to';
    const XML_PATH_EMAIL_FROM_NAME = 'lm_varnish_cache/purge_without_regen/email_from_name';
    const XML_PATH_EMAIL_FROM_EMAIL = 'lm_varnish_cache/purge_without_regen/email_from_email';
    const XML_PATH_EMAIL_REPLY_TO = 'lm_varnish_cache/purge_without_regen/email_reply_to';

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
     * @return string
     */
    public function getNotificationEmailTo(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_EMAIL_TO);
    }

    /**
     * @return string
     */
    public function getNotificationEmailFromEmail(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_EMAIL_FROM_EMAIL);
    }

    /**
     * @return string
     */
    public function getNotificationEmailFromName(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_EMAIL_FROM_NAME);
    }

    /**
     * @return string
     */
    public function getNotificationEmailReplyTo(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_EMAIL_REPLY_TO);
    }
}
