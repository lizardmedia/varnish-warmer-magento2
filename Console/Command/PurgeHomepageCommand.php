<?php
/**
 * File: PurgeHomepageCommand.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2018 Lizard Media (http://lizardmedia.pl)
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
    const CLI_COMMAND = 'lm-varnish:cache-purge-homepage';

    /**
     * {@inheritdoc}
     */
    protected function configure()
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
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->varnishPurger->setStoreViewId((int) $input->getOption(self::STORE_VIEW_ID));
        $this->varnishPurger->purgeHomepage();
    }
}
