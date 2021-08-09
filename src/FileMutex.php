<?php

declare(strict_types=1);

namespace Yiisoft\Mutex\File;

use Yiisoft\Files\FileHelper;
use Yiisoft\Mutex\MutexInterface;
use Yiisoft\Mutex\RetryAcquireTrait;

use function chmod;
use function clearstatcache;
use function fclose;
use function fileinode;
use function flock;
use function fopen;
use function fstat;
use function md5;
use function unlink;

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
     * @var resource|closed-resource|null Stores opened lock file resource.
     */
    private $lockResource = null;

    /**
     * @param string $name Mutex name.
     * @param string $mutexPath The directory to store mutex files.
     */
    public function __construct(string $name, string $mutexPath)
    {
        $this->name = $name;
        $this->mutexPath = $mutexPath;
    }

    public function __destruct()
    {
        $this->release();
    }

    public function acquire(int $timeout = 0): bool
    {
        $filePath = $this->getLockFilePath($this->name);

        return $this->retryAcquire($timeout, function () use ($filePath) {
            $resource = fopen($filePath, 'wb+');

            if ($resource === false) {
                return false;
            }

            if ($this->fileMode !== null) {
                @chmod($filePath, $this->fileMode);
            }

            if (!flock($resource, LOCK_EX | LOCK_NB)) {
                fclose($resource);
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
            if (DIRECTORY_SEPARATOR !== '\\' && fstat($resource)['ino'] !== @fileinode($filePath)) {
                clearstatcache(true, $filePath);
                flock($resource, LOCK_UN);
                fclose($resource);

                return false;
            }

            $this->lockResource = $resource;

            return true;
        });
    }

    public function release(): void
    {
        if (!is_resource($this->lockResource)) {
            return;
        }

        if (DIRECTORY_SEPARATOR === '\\') {
            // Under windows, it's not possible to delete a file opened via fopen (either by own or other process).
            // That's why we must first unlock and close the handle and then *try* to delete the lock file.
            flock($this->lockResource, LOCK_UN);
            fclose($this->lockResource);
            @unlink($this->getLockFilePath($this->name));
        } else {
            // Under unix, it's possible to delete a file opened via fopen (either by own or other process).
            // That's why we must unlink (the currently locked) lock file first and then unlock and close the handle.
            @unlink($this->getLockFilePath($this->name));
            flock($this->lockResource, LOCK_UN);
            fclose($this->lockResource);
        }

        $this->lockResource = null;
    }

    /**
     * Returns a new instance with the specified file mode.
     *
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
     * Returns a new instance with the specified directory mode.
     *
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

    /**
     * Generates path for lock file.
     *
     * @param string $name The name for lock file.
     *
     * @return string The generated path for lock file.
     */
    private function getLockFilePath(string $name): string
    {
        FileHelper::ensureDirectory($this->mutexPath, $this->directoryMode);
        return $this->mutexPath . DIRECTORY_SEPARATOR . md5($name) . '.lock';
    }
}
