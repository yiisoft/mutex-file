<?php

declare(strict_types=1);

namespace Yiisoft\Mutex\File\Tests;

use function md5;
use function sys_get_temp_dir;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function getMutexDirectoryPath(): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . md5(self::class);
    }

    protected function getMutexLockFilePath(string $mutexName): string
    {
        return $this->getMutexDirectoryPath() . DIRECTORY_SEPARATOR . md5($mutexName) . '.lock';
    }
}
