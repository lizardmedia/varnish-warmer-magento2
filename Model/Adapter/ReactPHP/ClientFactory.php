<?php
declare(strict_types=1);

/**
 * File: ClientFactory.php
 *
 * @author      Maciej Sławik <maciekslawik@gmail.com>
 * Github:      https://github.com/maciejslawik
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
