<?php

declare(strict_types=1);

/**
 * File: PurgeHomepage.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2019 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Controller\Adminhtml\Purge;

use LizardMedia\VarnishWarmer\Console\Command\PurgeHomepageCommand;
use LizardMedia\VarnishWarmer\Controller\Adminhtml\Purge;

/**
 * Class PurgeHomepage
 * @package LizardMedia\VarnishWarmer\Controller\Adminhtml\Purge
 */
class PurgeHomepage extends Purge
{
    /**
     * @return string
     */
    protected function getCliCommand(): string
    {
        return PurgeHomepageCommand::CLI_COMMAND;
    }
}
