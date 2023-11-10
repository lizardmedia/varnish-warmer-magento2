<?php

declare(strict_types=1);

/**
 * File: RegenerateProductsCacheCommand.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2019 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RegenerateProductsCacheCommand
 * @package LizardMedia\VarnishWarmer\Console\Command
 */
class RegenerateProductsCacheCommand extends AbstractPurgeCommand
{
    /**
     * @var string
     */
    public const CLI_COMMAND = 'lm-varnish:regenerate-products-cache';

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName(self::CLI_COMMAND)
            ->setDescription(
                'Get all active, enabled and visible products, clear and regenerate varnish cache by URL'
            )->addOption(
                self::STORE_VIEW_ID,
                null,
                InputOption::VALUE_OPTIONAL
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->passStoreViewIfSet($input);
        $this->varnishActionManager->purgeAndRegenerateProducts();
        return 0;
    }
}
