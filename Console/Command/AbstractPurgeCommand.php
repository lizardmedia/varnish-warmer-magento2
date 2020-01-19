<?php

declare(strict_types=1);

/**
 * File: AbstractPurgeCommand.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2019 Lizard Media (http://lizardmedia.pl)
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
    protected const STORE_VIEW_ID = 'store';

    /**
     * @var VarnishPurgerInterface
     */
    protected $varnishPurger;

    /**
     * AbstractPurgeCommand constructor.
     * @param VarnishPurgerInterface $varnishPurger
     * @param null $name
     */
    public function __construct(
        VarnishPurgerInterface $varnishPurger,
        $name = null
    ) {
        parent::__construct($name);
        $this->varnishPurger = $varnishPurger;
    }
}
