<?php

declare(strict_types=1);

namespace Yiisoft\Mutex\File;

use Yiisoft\Mutex\MutexFactoryInterface;
use Yiisoft\Mutex\MutexInterface;

/**
 * Allows creating file mutex objects.
 */
class FileMutexFactory implements MutexFactoryInterface
{
    private string $mutexPath;
    private bool $autoRelease;
    private ?int $fileMode = null;
    private int $directoryMode = 0775;

    /**
     * @param string $mutexPath The directory to store mutex files.
     * @param bool $autoRelease Whether to automatically release lock when PHP script ends.
     */
    public function __construct(string $mutexPath, bool $autoRelease = true)
    {
        $this->mutexPath = $mutexPath;
        $this->autoRelease = $autoRelease;
    }

    /**
     * @var int The permission to be set for newly created mutex files.
     * This value will be used by PHP {@see chmod()} function. No umask will be applied.
     */
    public function withFileMode(int $fileMode): self
    {
        $new = clone $this;
        $new->fileMode = $fileMode;
        return $new;
    }

    /**
     * @var int The permission to be set for newly created directories.
     * This value will be used by PHP {@see chmod()} function. No umask will be applied.
     * Defaults to 0775, meaning the directory is read-writable by owner and group,
     * but read-only for other users.
     */
    public function withDirectoryMode(int $directoryMode): self
    {
        $new = clone $this;
        $new->directoryMode = $directoryMode;
        return $new;
    }

    public function create(string $name): MutexInterface
    {
        return (new FileMutex($name, $this->mutexPath, $this->autoRelease))
            ->withFileMode($this->fileMode)
            ->withDirectoryMode($this->directoryMode);
    }
}
