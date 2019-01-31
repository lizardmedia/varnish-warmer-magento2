<?php
declare(strict_types=1);

/**
 * File: ClientFactory.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2019 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Model\Adapter\ReactPHP;

use React\EventLoop\LoopInterface;
use React\HttpClient\Client;

/**
 * Class ClientFactory
 * @package LizardMedia\VarnishWarmer\Model\Adapter\ReactPHP
 */
class ClientFactory
{
    /**
     * @param LoopInterface $loop
     * @return Client
     */
    public function create(LoopInterface $loop): Client
    {
        return new Client($loop);
    }
}
