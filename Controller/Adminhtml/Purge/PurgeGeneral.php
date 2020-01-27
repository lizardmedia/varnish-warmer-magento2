<?php

declare(strict_types=1);

/**
 * File: PurgeGeneral.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2019 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Controller\Adminhtml\Purge;

use LizardMedia\VarnishWarmer\Console\Command\PurgeGeneralCommand;
use LizardMedia\VarnishWarmer\Controller\Adminhtml\Purge;

/**
 * Class PurgeGeneral
 * @package LizardMedia\VarnishWarmer\Controller\Adminhtml\Purge
 */
class PurgeGeneral extends Purge
{
    /**
     * @return string
     */
    protected function getCliCommand(): string
    {
        return PurgeGeneralCommand::CLI_COMMAND;
    }
}
