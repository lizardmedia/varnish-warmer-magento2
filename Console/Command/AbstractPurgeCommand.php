<?php

declare(strict_types=1);

/**
 * File: AbstractPurgeCommand.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2019 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Console\Command;

use LizardMedia\VarnishWarmer\Api\VarnishActionManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

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
     * @var VarnishActionManagerInterface
     */
    protected $varnishActionManager;

    /**
     * AbstractPurgeCommand constructor.
     * @param VarnishActionManagerInterface $varnishActionManager
     * @param null $name
     */
    public function __construct(
        VarnishActionManagerInterface $varnishActionManager,
        $name = null
    ) {
        parent::__construct($name);
        $this->varnishActionManager = $varnishActionManager;
    }

    /**
     * @param InputInterface $input
     * @return void
     */
    protected function passStoreViewIfSet(InputInterface $input): void
    {
        $storeViewId = $input->getOption(self::STORE_VIEW_ID);

        if (!empty($storeViewId)) {
            $this->varnishActionManager->setStoreViewId((int) $storeViewId);
        }
    }
}
