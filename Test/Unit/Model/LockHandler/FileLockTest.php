<?php

declare(strict_types=1);

/**
 * @author Paweł Majcherczyk <pawel.majcherczyk@lizardmedia.pl>
 * @copyright Copyright (C) 2026 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Test\Unit\Model\LockHandler;

use LizardMedia\VarnishWarmer\Model\LockHandler\FileLock;
use LizardMedia\VarnishWarmer\Test\Unit\Instantiator;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File;
use PHPUnit\Framework\TestCase;

final class FileLockTest extends TestCase
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

    public function testLockIsHeldInFlagFileUntilUnlocked(): void
    {
        $lock = $this->createLock();
        self::assertFalse($lock->isLocked());

        $lock->lock();

        self::assertTrue($lock->isLocked());
        self::assertFileExists($this->magentoRoot . '/var/log/varnish/.varnish.lock.flag');
        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $lock->getLockDate());

        $lock->unlock();

        self::assertFalse($lock->isLocked());
        self::assertFileDoesNotExist($this->magentoRoot . '/var/log/varnish/.varnish.lock.flag');
    }

    public function testLockIsSharedBetweenInstances(): void
    {
        $this->createLock()->lock();

        self::assertTrue($this->createLock()->isLocked());
    }

    private function createLock(): FileLock
    {
        $directoryList = $this->createStub(DirectoryList::class);
        $directoryList->method('getRoot')->willReturn($this->magentoRoot);

        return Instantiator::create(FileLock::class, [
            DirectoryList::class => $directoryList,
            File::class => new File(),
        ]);
    }
}
