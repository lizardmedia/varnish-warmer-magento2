<?php

declare(strict_types=1);

/**
 * @author Paweł Majcherczyk <pawel.majcherczyk@lizardmedia.pl>
 * @copyright Copyright (C) 2026 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Test\Unit\Model\ProgressHandler;

use LizardMedia\VarnishWarmer\Api\ProgressHandler\ProgressDataInterface;
use LizardMedia\VarnishWarmer\Model\ProgressHandler\ProgressBarRenderer;
use PHPUnit\Framework\TestCase;

final class ProgressBarRendererTest extends TestCase
{
    public function testRendersProgressOfRunningProcess(): void
    {
        self::assertSame(
            'REGENERATE progress: <progress max="10" value="3"></progress> (3/10)',
            (new ProgressBarRenderer())->getProgressHtml($this->createProgressData('REGENERATE', 3, 10))
        );
    }

    public function testRendersNothingBeforeFirstUrlIsProcessed(): void
    {
        self::assertSame('', (new ProgressBarRenderer())->getProgressHtml($this->createProgressData('PURGE', 0, 10)));
    }

    private function createProgressData(string $processType, int $current, int $total): ProgressDataInterface
    {
        $progressData = $this->createStub(ProgressDataInterface::class);
        $progressData->method('getProcessType')->willReturn($processType);
        $progressData->method('getCurrent')->willReturn($current);
        $progressData->method('getTotal')->willReturn($total);

        return $progressData;
    }
}
