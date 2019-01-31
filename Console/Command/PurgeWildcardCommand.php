<?php
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
    const CLI_COMMAND = 'lm-varnish:cache-purge-wildcard';

    /**
     * {@inheritdoc}
     */
    protected function configure()
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
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->varnishPurger->setStoreViewId((int) $input->getOption(self::STORE_VIEW_ID));
        $this->varnishPurger->purgeWildcard();
    }
}
