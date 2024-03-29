<?php

declare(strict_types=1);

namespace Yiisoft\Mutex\File\Tests;

use Yiisoft\Mutex\File\FileMutex;

use function microtime;

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
        $mutexName = __METHOD__;
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

    public function testDeleteLockFile(): void
    {
        $mutexName = 'testDeleteLockFile';
        $mutex = $this->createMutex($mutexName);

        $mutex->acquire();
        $this->assertFileExists($this->getMutexLockFilePath($mutexName));

        $mutex->release();
        $this->assertFileDoesNotExist($this->getMutexLockFilePath($mutexName));
    }

    public function testDestruct(): void
    {
        $mutexName = 'testDestruct';
        $mutex = $this->createMutex($mutexName);
        $file = $this->getMutexLockFilePath($mutexName);

        $this->assertTrue($mutex->acquire());
        $this->assertFileExists($file);

        unset($mutex);
        $this->assertFileDoesNotExist($file);
    }

    private function createMutex(string $name): FileMutex
    {
        return new FileMutex($name, $this->getMutexDirectoryPath());
    }
}
