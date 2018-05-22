<?php
/**
 * File: PurgeUrlCommand.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2018 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Console\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class PurgeUrlCommand
 * @package LizardMedia\VarnishWarmer\Console\Command
 */
class PurgeUrlCommand extends AbstractPurgeCommand
{
    const CLI_COMMAND = 'lm-varnish:cache-refresh-url';

    const URL_ARGUMENT = 'url';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::CLI_COMMAND)
            ->setDescription('Clear varnish cache (make PURGE) and re-generate URL')
            ->addOption(
                self::VERIFY_PEER_PARAM,
                null,
                InputOption::VALUE_OPTIONAL
            )
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
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $url = $input->getArgument(self::URL_ARGUMENT);
        if ($this->shouldSkipVerifyPeer($input)) {
            $this->cacheCleaner->verifyPeer = false;
        }
        $this->cacheCleaner->setStoreViewId((int)$input->getOption(self::STORE_VIEW_ID));
        $this->cacheCleaner->purgeAndRegenerateUrl($url);
    }
}
