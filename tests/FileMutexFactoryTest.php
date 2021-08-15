<?php

declare(strict_types=1);

namespace Yiisoft\Mutex\File\Tests;

use Yiisoft\Mutex\File\FileMutex;
use Yiisoft\Mutex\File\FileMutexFactory;
use Yiisoft\Mutex\MutexInterface;

final class FileMutexFactoryTest extends TestCase
{
    public function testCreateAndAcquire(): void
    {
        $mutexName = 'testCreateAndAcquire';
        $factory = (new FileMutexFactory($this->getMutexDirectoryPath(), 0777, 0664));
        $mutex = $factory->createAndAcquire($mutexName);

        $this->assertInstanceOf(MutexInterface::class, $mutex);
        $this->assertInstanceOf(FileMutex::class, $mutex);

        $this->assertFileExists($this->getMutexLockFilePath($mutexName));
        $this->assertFalse($mutex->acquire());
        $mutex->release();

        $this->assertFileDoesNotExist($this->getMutexLockFilePath($mutexName));
        $this->assertTrue($mutex->acquire());
        $this->assertFileExists($this->getMutexLockFilePath($mutexName));

        $mutex->release();
        $this->assertFileDoesNotExist($this->getMutexLockFilePath($mutexName));
    }
}
