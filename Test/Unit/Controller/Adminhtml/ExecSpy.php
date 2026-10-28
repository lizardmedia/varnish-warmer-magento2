<?php

declare(strict_types=1);

/**
 * @author Paweł Majcherczyk <pawel.majcherczyk@lizardmedia.pl>
 * @copyright Copyright (C) 2026 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Test\Unit\Controller\Adminhtml {
    final class ExecSpy
    {
        public static array $commands = [];

        public static function reset(): void
        {
            self::$commands = [];
        }
    }
}

namespace LizardMedia\VarnishWarmer\Controller\Adminhtml {
    use LizardMedia\VarnishWarmer\Test\Unit\Controller\Adminhtml\ExecSpy;

    function exec(string $command): string
    {
        ExecSpy::$commands[] = $command;

        return '';
    }
}
