<?php

declare(strict_types=1);

/**
 * File: PurgeHomepageCommand.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2019 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class PurgeHomepageCommand
 * @package LizardMedia\VarnishWarmer\Console\Command
 */
class PurgeHomepageCommand extends AbstractPurgeCommand
{
    /**
     * @var string
     */
    public const CLI_COMMAND = 'lm-varnish:cache-purge-homepage';

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName(self::CLI_COMMAND)
            ->setDescription('Purge: homepage; Regenerate: homepage')
            ->addOption(
                self::STORE_VIEW_ID,
                null,
                InputOption::VALUE_OPTIONAL
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->passStoreViewIfSet($input);
        $this->varnishActionManager->purgeHomepage();
    }
}
