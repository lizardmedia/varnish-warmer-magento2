<?php

declare(strict_types=1);

/**
 * File: PurgeWildcard.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2019 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Controller\Adminhtml\Purge;

use LizardMedia\VarnishWarmer\Console\Command\PurgeWildcardCommand;
use LizardMedia\VarnishWarmer\Controller\Adminhtml\Purge;

/**
 * Class PurgeWildcard
 * @package LizardMedia\VarnishWarmer\Controller\Adminhtml\Purge
 */
class PurgeWildcard extends Purge
{
    /**
     * @return string
     */
    protected function getCliCommand(): string
    {
        return PurgeWildcardCommand::CLI_COMMAND;
    }
}
