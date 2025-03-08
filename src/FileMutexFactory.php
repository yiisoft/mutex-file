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
    private int $directoryMode;
    private ?int $fileMode;

    /**
     * @param string $mutexPath The directory to store mutex files.
     * @param int $directoryMode The permission to be set for newly created directories.
     * This value will be used by PHP {@see chmod()} function. No umask will be applied. Defaults to 0775,
     * meaning the directory is read-writable by owner and group, but read-only for other users.
     * @param int|null $fileMode The permission to be set for newly created mutex files.
     * This value will be used by PHP {@see chmod()} function. No umask will be applied.
     */
    public function __construct(string $mutexPath, int $directoryMode = 0775, ?int $fileMode = null)
    {
        $this->mutexPath = $mutexPath;
        $this->directoryMode = $directoryMode;
        $this->fileMode = $fileMode;
    }

    public function create(string $name): MutexInterface
    {
        return new FileMutex($name, $this->mutexPath, $this->directoryMode, $this->fileMode);
    }
}
