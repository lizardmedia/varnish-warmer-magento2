<?php

declare(strict_types=1);

/**
 * @author Paweł Majcherczyk <pawel.majcherczyk@lizardmedia.pl>
 * @copyright Copyright (C) 2026 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Test\Unit\Model\ProgressHandler;

use LizardMedia\VarnishWarmer\Model\ProgressHandler\FileQueueProgressLogger;
use LizardMedia\VarnishWarmer\Model\ProgressHandler\ProgressData;
use LizardMedia\VarnishWarmer\Model\ProgressHandler\ProgressDataFactory;
use LizardMedia\VarnishWarmer\Test\Unit\Instantiator;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class FileQueueProgressLoggerTest extends TestCase
{
    private string $magentoRoot;

    protected function setUp(): void
    {
        $this->magentoRoot = sys_get_temp_dir() . '/varnish-warmer-' . bin2hex(random_bytes(6));
        mkdir($this->magentoRoot);
    }

    protected function tearDown(): void
    {
        File::rmdirRecursive($this->magentoRoot);
    }

    public function testReadsBackLoggedProgress(): void
    {
        $progressLogger = $this->createProgressLogger();

        $progressLogger->logProgress('REGENERATE', 3, 10);
        $progressData = $progressLogger->getProgressData();

        self::assertFileExists($this->magentoRoot . '/var/log/varnish/.queue_progress.log');
        self::assertSame('REGENERATE', $progressData->getProcessType());
        self::assertSame(3, $progressData->getCurrent());
        self::assertSame(10, $progressData->getTotal());
    }

    public function testReturnsEmptyProgressWhenProgressLogIsCorrupted(): void
    {
        mkdir($this->magentoRoot . '/var/log/varnish', 0777, true);
        file_put_contents($this->magentoRoot . '/var/log/varnish/.queue_progress.log', 'not json');

        self::assertNull($this->createProgressLogger()->getProgressData()->getCurrent());
    }

    public function testReturnsEmptyProgressBeforeAnythingWasLogged(): void
    {
        self::assertNull($this->createProgressLogger()->getProgressData()->getCurrent());
    }

    private function createProgressLogger(): FileQueueProgressLogger
    {
        $directoryList = $this->createStub(DirectoryList::class);
        $directoryList->method('getRoot')->willReturn($this->magentoRoot);

        $progressDataFactory = $this->createStub(ProgressDataFactory::class);
        $progressDataFactory->method('create')->willReturnCallback(
            static fn (): ProgressData => (new ReflectionClass(ProgressData::class))->newInstanceWithoutConstructor()
        );

        return Instantiator::create(FileQueueProgressLogger::class, [
            DirectoryList::class => $directoryList,
            File::class => new File(),
            ProgressDataFactory::class => $progressDataFactory,
            Json::class => new Json(),
        ]);
    }
}
