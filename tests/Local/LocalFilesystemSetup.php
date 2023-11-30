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

use Shudd3r\Filesystem\Tests\Fixtures\TestRoot;
use Shudd3r\Filesystem\Tests\Fixtures\TempFiles;
use Shudd3r\Filesystem\Tests\Fixtures\Override;


trait LocalFilesystemSetup
{
    private static TempFiles $temp;
    private static string    $cwd;

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
        Override::reset();
    }

    protected function root(array $structure = null, array $access = []): TestRoot\LocalTestRoot
    {
        return new TestRoot\LocalTestRoot(self::$temp, $structure ?? $this->exampleStructure(), $access);
    }

    protected function path(string $name = ''): string
    {
        return self::$temp->pathname($name);
    }

    private function assertIOException(string $exception, callable $procedure, string $override, $argValue = null): void
    {
        Override::set($override, function () {
            trigger_error('emulated warning', E_USER_WARNING);
            return false;
        }, $argValue);
        $this->assertExceptionType($exception, $procedure);
        Override::remove($override);
    }
}
