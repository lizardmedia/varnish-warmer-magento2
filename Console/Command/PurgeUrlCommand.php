<?php

declare(strict_types=1);

/**
 * File: PurgeUrlCommand.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2019 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class PurgeUrlCommand
 * @package LizardMedia\VarnishWarmer\Console\Command
 */
class PurgeUrlCommand extends AbstractPurgeCommand
{
    /**
     * @var string
     */
    public const CLI_COMMAND = 'lm-varnish:cache-refresh-url';

    /**
     * @var string
     */
    private const URL_ARGUMENT = 'url';

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName(self::CLI_COMMAND)
            ->setDescription('Clear varnish cache (make PURGE) and re-generate URL')
            ->addArgument(
                self::URL_ARGUMENT,
                InputArgument::REQUIRED,
                'Type a URL to PURGE and re-generate'
            )->addOption(
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
        $url = $input->getArgument(self::URL_ARGUMENT);
        $this->varnishPurger->setStoreViewId((int) $input->getOption(self::STORE_VIEW_ID));
        $this->varnishPurger->purgeAndRegenerateUrl($url);
    }
}
