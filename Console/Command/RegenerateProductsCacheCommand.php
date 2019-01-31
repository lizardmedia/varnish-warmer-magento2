<?php
/**
 * File: RegenerateProductsCacheCommand.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2019 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class RegenerateProductsCacheCommand
 * @package LizardMedia\VarnishWarmer\Console\Command
 */
class RegenerateProductsCacheCommand extends AbstractPurgeCommand
{
    /**
     * @var string
     */
    const CLI_COMMAND = 'lm-varnish:regenerate-products-cache';

    /**
     * {@inheritdoc}
     */
    protected function configure()
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
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->varnishPurger->setStoreViewId((int) $input->getOption(self::STORE_VIEW_ID));
        $this->varnishPurger->purgeAndRegenerateProducts();
    }
}
