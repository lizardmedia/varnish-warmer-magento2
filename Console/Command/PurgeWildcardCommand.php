<?php

declare(strict_types=1);

/**
 * File: PurgeWildcardCommand.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2019 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class PurgeWildcardCommand
 * @package LizardMedia\VarnishWarmer\Console\Command
 */
class PurgeWildcardCommand extends AbstractPurgeCommand
{
    /**
     * @var string
     */
    public const CLI_COMMAND = 'lm-varnish:cache-purge-wildcard';

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName(self::CLI_COMMAND)
            ->setDescription('Purge: *; Regenerate: homepage, categories, products')
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
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->passStoreViewIfSet($input);
        $this->varnishActionManager->purgeWildcard();
        return 0;
    }
}
