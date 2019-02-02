<?php
/**
 * File: FileLock.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2019 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Model\LockHandler;

use Exception;
use LizardMedia\VarnishWarmer\Api\LockHandler\LockInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File;

/**
 * Class FileLock
 * @package LizardMedia\VarnishWarmer\Model\LockHandler
 */
final class FileLock implements LockInterface
{
    /**
     * @var string
     */
    const LOCK_DIR = '/var/log/varnish/';
    const LOCK_FILE = '.varnish.lock.flag';

    /**
     * @var File
     */
    private $fileHandler;

    /**
     * @var string
     */
    private $lockDir;

    /**
     * @var string
     */
    private $lockFile;

    /**
     * FileLock constructor.
     * @param DirectoryList $directoryList
     * @param File $fileHandler
     */
    public function __construct(
        DirectoryList $directoryList,
        File $fileHandler
    ) {
        $this->lockDir = $directoryList->getRoot() . self::LOCK_DIR;
        $this->lockFile = $directoryList->getRoot() . self::LOCK_DIR . self::LOCK_FILE;
        $this->fileHandler = $fileHandler;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function lock(): void
    {
        $this->fileHandler->checkAndCreateFolder($this->lockDir);
        $this->fileHandler->write($this->lockFile, date('Y-m-d H:i:s'));
    }

    /**
     * @return void
     */
    public function unlock(): void
    {
        $this->fileHandler->rm($this->lockFile);
    }

    /**
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->fileHandler->fileExists($this->lockFile);
    }

    /**
     * @return string
     */
    public function getLockDate(): string
    {
        return $this->fileHandler->read($this->lockFile);
    }
}
