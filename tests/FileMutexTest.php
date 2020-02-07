<?php

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
    protected function createMutex(): FileMutex
    {
        return new FileMutex(sys_get_temp_dir());
    }

    /**
     * @dataProvider mutexDataProvider()
     *
     * @param string $mutexName
     */
    public function testDeleteLockFile($mutexName): void
    {
        $mutex = $this->createMutex();

        $mutex->acquire($mutexName);
        $this->assertFileExists($mutex->getLockFilePath($mutexName));

        $mutex->release($mutexName);
        $this->assertFileNotExists($mutex->getLockFilePath($mutexName));
    }
}
