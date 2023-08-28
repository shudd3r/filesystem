<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Tests\Local;

use Shudd3r\Filesystem\Tests\FilesystemTests;
use Shudd3r\Filesystem\Tests\Fixtures\TempFiles;


abstract class LocalFilesystemTests extends FilesystemTests
{
    protected static TempFiles $temp;

    private static string $cwd;

    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__) . '/Fixtures/native-override/local.php';
        self::$cwd  = getcwd();
        self::$temp = new TempFiles(basename(static::class));
    }

    public static function tearDownAfterClass(): void
    {
        chdir(self::$cwd);
    }

    protected function tearDown(): void
    {
        self::$temp->clear();
        parent::tearDown();
    }

    protected function assertIOException(string $exception, callable $procedure, string $override, $argValue = null): void
    {
        $this->override($override, function () {
            trigger_error('emulated warning', E_USER_WARNING);
            return false;
        }, $argValue);
        $this->assertExceptionType($exception, $procedure);
        $this->removeOverride($override);
    }
}
