<?php
/**
 * File: VarnishUrlRegeneratorInterface.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2019 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Api\QueueHandler;

/**
 * Interface VarnishUrlRegenerator
 * @package LizardMedia\VarnishWarmer\Api\QueueHandler
 */
interface VarnishUrlRegeneratorInterface
{
    /**
     * @param string $url
     * @return void
     */
    public function addUrlToRegenerate(string $url): void;

    /**
     * @return void
     */
    public function regenerate(): void;
}
