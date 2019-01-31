<?php
/**
 * File: RegenerationHandler.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2019 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

/**
 * Class RegenerationHandler
 * @package LizardMedia\VarnishWarmer\Logger\Handler
 */
class RegenerationHandler extends Base
{
    /**
     * @var string
     */
    protected $fileName = '/var/log/varnish/regeneration.log';

    /**
     * @var int
     */
    protected $loggerType = Logger::DEBUG;
}
