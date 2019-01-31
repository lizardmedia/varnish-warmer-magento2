<?php
/**
 * File: PurgeHandler.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2019 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

/**
 * Class PurgeHandler
 * @package LizardMedia\VarnishWarmer\Logger\Handler
 */
class PurgeHandler extends Base
{
    /**
     * @var string
     */
    protected $fileName = '/var/log/varnish/purge.log';

    /**
     * @var int
     */
    protected $loggerType = Logger::DEBUG;
}
