<?php
/**
 * File: PurgeWildcardWithoutRegenerationCommand.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2018 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class PurgeWildcardWithoutRegenerationCommand
 * @package LizardMedia\VarnishWarmer\Console\Command
 */
class PurgeWildcardWithoutRegenerationCommand extends AbstractPurgeCommand
{
    const CLI_COMMAND = 'lm-varnish:cache-purge-force';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::CLI_COMMAND)
            ->setDescription('Purge: *; Regenerate: none; Skip process lock check')
            ->addOption(
                self::VERIFY_PEER_PARAM,
                null,
                InputOption::VALUE_OPTIONAL
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
        if ($this->shouldSkipVerifyPeer($input)) {
            $this->cacheCleaner->verifyPeer = false;
        }
        $this->cacheCleaner->setStoreViewId((int)$input->getOption(self::STORE_VIEW_ID));
        $this->cacheCleaner->purgeWildcardWithoutRegen();
    }
}
