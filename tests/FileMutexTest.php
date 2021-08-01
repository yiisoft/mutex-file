<?php

declare(strict_types=1);

namespace Yiisoft\Mutex\File\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Mutex\File\FileMutex;

final class FileMutexTest extends TestCase
{
    public function testMutexAcquire(): void
    {
        $mutex = $this->createMutex('testMutexAcquire');

        $this->assertTrue($mutex->acquire());
        $mutex->release();
    }

    public function testThatMutexLockIsWorking(): void
    {
        $mutexOne = $this->createMutex('testThatMutexLockIsWorking');
        $mutexTwo = $this->createMutex('testThatMutexLockIsWorking');

        $this->assertTrue($mutexOne->acquire());
        $this->assertFalse($mutexTwo->acquire());
        $mutexOne->release();
        $mutexTwo->release();

        $this->assertTrue($mutexTwo->acquire());
        $mutexTwo->release();
    }

    public function testThatMutexLockIsWorkingOnTheSameComponent(): void
    {
        $mutex = $this->createMutex('testThatMutexLockIsWorkingOnTheSameComponent');

        $this->assertTrue($mutex->acquire());
        $this->assertFalse($mutex->acquire());

        $mutex->release();
        $mutex->release();
    }

    public function testTimeout(): void
    {
        $mutexName = __FUNCTION__;
        $mutexOne = $this->createMutex($mutexName);
        $mutexTwo = $this->createMutex($mutexName);

        $this->assertTrue($mutexOne->acquire());
        $microtime = microtime(true);
        $this->assertFalse($mutexTwo->acquire(1));
        $diff = microtime(true) - $microtime;
        $this->assertTrue($diff >= 1 && $diff < 2);
        $mutexOne->release();
        $mutexTwo->release();
    }

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
