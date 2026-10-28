<?php

declare(strict_types=1);

/**
 * @author Paweł Majcherczyk <pawel.majcherczyk@lizardmedia.pl>
 * @copyright Copyright (C) 2026 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Test\Unit\Etc;

use LizardMedia\VarnishWarmer\Controller\Adminhtml\Purge;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;

final class AclTest extends TestCase
{
    private const MODULE_DIR = __DIR__ . '/../../..';

    public function testEveryResourceUsedByMenuIsDeclared(): void
    {
        $menuResources = array_map(
            'strval',
            $this->loadXml('etc/adminhtml/menu.xml')->xpath('//add/@resource')
        );

        self::assertNotEmpty($menuResources);
        foreach ($menuResources as $resource) {
            self::assertContains($resource, $this->declaredResources(), 'menu.xml uses ' . $resource);
        }
    }

    public function testEveryResourceUsedBySystemConfigurationIsDeclared(): void
    {
        $sectionResources = array_map(
            'strval',
            $this->loadXml('etc/adminhtml/system.xml')->xpath('//section/resource')
        );

        self::assertNotEmpty($sectionResources);
        foreach ($sectionResources as $resource) {
            self::assertContains($resource, $this->declaredResources(), 'system.xml uses ' . $resource);
        }
    }

    public function testResourceRequiredByControllersIsDeclared(): void
    {
        self::assertContains(Purge::ADMIN_RESOURCE, $this->declaredResources());
    }

    private function declaredResources(): array
    {
        return array_map('strval', $this->loadXml('etc/acl.xml')->xpath('//resource/@id'));
    }

    private function loadXml(string $path): SimpleXMLElement
    {
        return new SimpleXMLElement(file_get_contents(self::MODULE_DIR . '/' . $path));
    }
}
