<?php
/**
 * File: PurgeWithoutRegenNotificationMailerConfigProviderInterface.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2018 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Api\Config;

/**
 * Interface PurgeWithoutRegenNotificationMailerConfigProviderInterface
 * @package LizardMedia\VarnishWarmer\Api\Config
 */
interface PurgeWithoutRegenNotificationMailerConfigProviderInterface
{
    /**
     * @return string
     */
    public function getNotificationEmailTo(): string;

    /**
     * @return string
     */
    public function getNotificationEmailFromEmail(): string;

    /**
     * @return string
     */
    public function getNotificationEmailFromName(): string;

    /**
     * @return string
     */
    public function getNotificationEmailReplyTo(): string;
}
