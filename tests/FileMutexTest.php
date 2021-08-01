<?php

declare(strict_types=1);

namespace Yiisoft\Mutex\File\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Mutex\File\FileMutex;
use Yiisoft\Mutex\Tests\MutexTestTrait;

final class FileMutexTest extends TestCase
{
    use MutexTestTrait;

    /**
     * @throws \RuntimeException
     *
     * @return FileMutex
     */
    protected function createMutex(string $name): FileMutex
    {
        return new FileMutex($name, sys_get_temp_dir());
    }

    public function testDeleteLockFile(): void
    {
        $mutexName = 'testDeleteLockFile';
        $mutex = $this->createMutex($mutexName);

        $mutex->acquire();
        $this->assertFileExists($this->getMutexLockFilePath($mutexName));

        $mutex->release();
        $this->assertFileDoesNotExist($this->getMutexLockFilePath($mutexName));
    }

    private function getMutexLockFilePath(string $mutexName): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . md5($mutexName) . '.lock';
    }
}
