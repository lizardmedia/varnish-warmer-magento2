<?php
/**
 * File: AbstractPurgeCommand.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2018 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Console\Command;

use LizardMedia\VarnishWarmer\Api\VarnishPurgerInterface;
use Symfony\Component\Console\Command\Command;
use Magento\Framework\App\State;

/**
 * Class AbstractPurgeCommand
 * @package LizardMedia\VarnishWarmer\Console\Command
 */
class AbstractPurgeCommand extends Command
{
    /**
     * @var string
     */
    const STORE_VIEW_ID = 'store';

    /**
     * @var VarnishPurgerInterface
     */
    protected $varnishPurger;

    /**
     * AbstractPurgeCommand constructor.
     * @param State $state
     * @param VarnishPurgerInterface $varnishPurger
     * @param null $name
     */
    public function __construct(
        State $state,
        VarnishPurgerInterface $varnishPurger,
        $name = null
    ) {
        $this->varnishPurger = $varnishPurger;
        parent::__construct($name);
    }
}
