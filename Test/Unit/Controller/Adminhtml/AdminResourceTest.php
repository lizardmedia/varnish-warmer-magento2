<?php

declare(strict_types=1);

/**
 * @author Paweł Majcherczyk <pawel.majcherczyk@lizardmedia.pl>
 * @copyright Copyright (C) 2026 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Test\Unit\Controller\Adminhtml;

use LizardMedia\VarnishWarmer\Controller\Adminhtml\Purge\PurgeAll;
use LizardMedia\VarnishWarmer\Controller\Adminhtml\Purge\PurgeGeneral;
use LizardMedia\VarnishWarmer\Controller\Adminhtml\Purge\PurgeHomepage;
use LizardMedia\VarnishWarmer\Controller\Adminhtml\Purge\PurgeWildcard;
use LizardMedia\VarnishWarmer\Controller\Adminhtml\Purge\PurgeWildcardForce;
use LizardMedia\VarnishWarmer\Controller\Adminhtml\PurgeMultiple\Form as PurgeMultipleForm;
use LizardMedia\VarnishWarmer\Controller\Adminhtml\PurgeMultiple\Run as PurgeMultipleRun;
use LizardMedia\VarnishWarmer\Controller\Adminhtml\PurgeSingle\Form as PurgeSingleForm;
use LizardMedia\VarnishWarmer\Controller\Adminhtml\PurgeSingle\Run as PurgeSingleRun;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AdminResourceTest extends TestCase
{
    public static function adminControllers(): array
    {
        return [
            'purge homepage' => [PurgeHomepage::class],
            'purge general' => [PurgeGeneral::class],
            'purge all' => [PurgeAll::class],
            'purge wildcard' => [PurgeWildcard::class],
            'purge wildcard force' => [PurgeWildcardForce::class],
            'purge single form' => [PurgeSingleForm::class],
            'purge single run' => [PurgeSingleRun::class],
            'purge multiple form' => [PurgeMultipleForm::class],
            'purge multiple run' => [PurgeMultipleRun::class],
        ];
    }

    #[DataProvider('adminControllers')]
    public function testRequiresCacheHandlingPermission(string $controllerClass): void
    {
        self::assertSame('LizardMedia_VarnishWarmer::cache_handling', $controllerClass::ADMIN_RESOURCE);
    }
}
