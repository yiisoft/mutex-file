<?php

declare(strict_types=1);

namespace Yiisoft\Mutex\File;

use Yiisoft\Mutex\MutexFactory;
use Yiisoft\Mutex\MutexInterface;

/**
 * Allows creating file mutex objects.
 */
final class FileMutexFactory extends MutexFactory
{
    private string $mutexPath;
    private ?int $fileMode = null;
    private int $directoryMode = 0775;

    /**
     * @param string $mutexPath The directory to store mutex files.
     */
    public function __construct(string $mutexPath)
    {
        $this->mutexPath = $mutexPath;
    }

    public function create(string $name): MutexInterface
    {
        $mutex = (new FileMutex($name, $this->mutexPath))->withDirectoryMode($this->directoryMode);
        return $this->fileMode === null ? $mutex : $mutex->withFileMode($this->fileMode);
    }

    /**
     * Returns a new instance with the specified file mode.
     *
     * @param int The permission to be set for newly created mutex files.
     * This value will be used by PHP {@see chmod()} function. No umask will be applied.
     */
    public function withFileMode(int $fileMode): self
    {
        $new = clone $this;
        $new->fileMode = $fileMode;
        return $new;
    }

    /**
     * Returns a new instance with the specified directory mode.
     *
     * @param int The permission to be set for newly created directories.
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
}
