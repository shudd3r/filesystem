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
use Shudd3r\Filesystem\Tests\Fixtures\TestRoot;
use Shudd3r\Filesystem\Tests\Fixtures\TempFiles;
use Shudd3r\Filesystem\Tests\Fixtures\Override;


abstract class LocalFilesystemTests extends FilesystemTests
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

    protected function root(array $structure = null): TestRoot\LocalTestRoot
    {
        return new TestRoot\LocalTestRoot(self::$temp, $structure ?? $this->exampleStructure());
    }

    protected function path(string $name = ''): string
    {
        return self::$temp->pathname($name);
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

    /**
     * @param string         $function
     * @param callable|mixed $returnValue fn() => mixed
     * @param mixed          $argValue    Trigger override for this value
     */
    protected function override(string $function, $returnValue, $argValue = null): void
    {
        Override::set($function, $returnValue, $argValue);
    }

    protected function removeOverride(string $function): void
    {
        Override::remove($function);
    }

    protected function invalidRootPaths(): array
    {
        chdir($this->path());
        return [
            'file path'         => self::$temp->file('foo/bar/baz.txt'),
            'not existing path' => self::$temp->pathname('not/exists'),
            'invalid symlink'   => self::$temp->symlink('not/exists', 'link1'),
            'valid symlink'     => self::$temp->symlink('foo/bar', 'link2'),
            'relative path'     => self::$temp->relative('./foo/bar'),
            'step-up path'      => self::$temp->pathname('foo/bar/..'),
            'empty path'        => '',
            'dot path'          => '.'
        ];
    }
}
