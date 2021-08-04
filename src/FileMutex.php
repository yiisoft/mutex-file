<?php

declare(strict_types=1);

namespace Yiisoft\Mutex\File;

use Yiisoft\Files\FileHelper;
use Yiisoft\Mutex\MutexInterface;
use Yiisoft\Mutex\RetryAcquireTrait;

/**
 * FileMutex implements mutex "lock" mechanism via local file system files.
 *
 * This component relies on PHP {@see flock()} function.
 *
 * > Note: this component can maintain the locks only for the single web server,
 * > it probably will not suffice in case you are using cloud server solution.
 *
 * > Warning: due to {@see flock()} function nature this component is unreliable when
 * > using a multithreaded server API like ISAPI.
 */
final class FileMutex implements MutexInterface
{
    use RetryAcquireTrait;

    private string $name;
    private string $mutexPath;
    private ?int $fileMode = null;
    private int $directoryMode = 0775;

    /**
     * @var resource Stores opened lock file resource.
     */
    private $lockResource;

    /**
     * @param string $name Mutex name.
     * @param string $mutexPath The directory to store mutex files.
     * @param bool $autoRelease Whether to automatically release lock when PHP script ends.
     */
    public function __construct(string $name, string $mutexPath, bool $autoRelease = true)
    {
        $this->name = $name;
        $this->mutexPath = $mutexPath;

        if ($autoRelease) {
            register_shutdown_function(function () {
                $this->release();
            });
        }
    }

    public function acquire(int $timeout = 0): bool
    {
        $filePath = $this->getLockFilePath($this->name);

        return $this->retryAcquire($timeout, function () use ($filePath) {
            $file = fopen($filePath, 'wb+');
            if ($file === false) {
                return false;
            }

            if ($this->fileMode !== null) {
                @chmod($filePath, $this->fileMode);
            }

            if (!flock($file, LOCK_EX | LOCK_NB)) {
                fclose($file);

                return false;
            }

            // Under unix, we delete the lock file before releasing the related handle. Thus, it's possible that we've
            // acquired a lock on a non-existing file here (race condition). We must compare the inode of the lock file
            // handle with the inode of the actual lock file.
            // If they do not match we simply continue the loop since we can assume the inodes will be equal on the
            // next try.
            // Example of race condition without inode-comparison:
            // Script A: locks file
            // Script B: opens file
            // Script A: unlinks and unlocks file
            // Script B: locks handle of *unlinked* file
            // Script C: opens and locks *new* file
            // In this case we would have acquired two locks for the same file path.
            if (DIRECTORY_SEPARATOR !== '\\' && fstat($file)['ino'] !== @fileinode($filePath)) {
                clearstatcache(true, $filePath);
                flock($file, LOCK_UN);
                fclose($file);

                return false;
            }

            $this->lockResource = $file;

            return true;
        });
    }

    public function release(): void
    {
        if ($this->lockResource === null) {
            return;
        }

        $isWindows = DIRECTORY_SEPARATOR === '\\';
        if ($isWindows) {
            // Under windows, it's not possible to delete a file opened via fopen (either by own or other process).
            // That's why we must first unlock and close the handle and then *try* to delete the lock file.
            flock($this->lockResource, LOCK_UN);
            fclose($this->lockResource);
            @unlink($this->getLockFilePath($this->name));
        } else {
            // Under unix, it's possible to delete a file opened via fopen (either by own or other process).
            // That's why we must unlink (the currently locked) lock file first and then unlock and close the handle.
            unlink($this->getLockFilePath($this->name));
            flock($this->lockResource, LOCK_UN);
            fclose($this->lockResource);
        }

        $this->lockResource = null;
    }

    /**
     * Generates path for lock file.
     *
     * @param string $name
     *
     * @return string
     */
    private function getLockFilePath(string $name): string
    {
        FileHelper::ensureDirectory($this->mutexPath, $this->directoryMode);
        return $this->mutexPath . DIRECTORY_SEPARATOR . md5($name) . '.lock';
    }

    /**
     * @param int $fileMode The permission to be set for newly created mutex files.
     * This value will be used by PHP {@see chmod()} function. No umask will be applied.
     */
    public function withFileMode(int $fileMode): self
    {
        $new = clone $this;
        $new->fileMode = $fileMode;
        return $new;
    }

    /**
     * @param int $directoryMode The permission to be set for newly created directories.
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
