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

use PHPUnit\Framework\TestCase;
use Shudd3r\Filesystem\Local\LocalDirectory;
use Shudd3r\Filesystem\Local\LocalFile;
use Shudd3r\Filesystem\Exception;
use Shudd3r\Filesystem\Tests\Fixtures;


class LocalDirectoryTest extends TestCase
{
    use Fixtures\TempFilesHandling;

    public function test_instance_can_only_be_created_with_real_directory_path(): void
    {
        foreach ($this->invalidDirectoryPaths() as $type => $path) {
            $this->assertNull($this->directory($path), "Failed for `$type`");
        }

        $path = self::$temp->directory('existing/directory');
        $this->assertSame($path, realpath($path));
        $this->assertTrue(is_dir($path));
        $this->assertInstanceOf(LocalDirectory::class, $this->directory($path));
    }

    public function test_pathname_returns_absolute_path_to_existing_directory(): void
    {
        $path = self::$temp->directory('foo/bar/baz');
        $this->assertSame($path, $this->directory($path)->pathname());
    }

    public function test_file_for_existing_pathname_returns_File(): void
    {
        self::$temp->file('foo/bar.txt');
        $this->assertFile($this->directory(), 'foo/bar.txt');
    }

    public function test_file_for_not_existing_pathname_returns_File(): void
    {
        $this->assertFile($this->directory(), 'foo/bar.txt');
    }

    public function test_file_for_linked_path_returns_File(): void
    {
        self::$temp->symlink(self::$temp->file('foo/bar.txt'), 'link/file.lnk');
        $this->assertFile($this->directory(), 'link\file.lnk');

        self::$temp->symlink(self::$temp->directory('foo'), 'path.lnk');
        $this->assertFile($this->directory(), 'path.lnk/bar.txt');
    }

    public function test_file_returns_null_for_non_file_paths(): void
    {
        foreach ($this->invalidRelativePaths(true) as $type => [$relativePath, $invalid]) {
            $procedure = fn () => $this->directory()->file($relativePath);
            $exception = $invalid ? Exception\InvalidPath::class : Exception\UnreachablePath::class;
            $this->assertExceptionType($procedure, $exception, "Failed for `$type`");
        }
    }

    public function test_subdirectory_for_existing_pathname_returns_Directory(): void
    {
        self::$temp->directory('foo/bar/baz');
        $this->assertDirectory($this->directory(), 'foo/bar/baz');
    }

    public function test_subdirectory_for_not_existing_pathname_returns_Directory(): void
    {
        $this->assertDirectory($this->directory(), 'foo/bar/baz');
    }

    public function test_subdirectory_for_linked_path_returns_Directory(): void
    {
        self::$temp->symlink(self::$temp->directory('foo/bar/baz'), 'link/dir.lnk');
        $this->assertDirectory($this->directory(), 'link/dir.lnk');

        self::$temp->symlink(self::$temp->directory('foo/bar'), 'path.lnk');
        $this->assertDirectory($this->directory(), 'path.lnk/baz');
    }

    public function test_subdirectoryPath_returns_null_for_non_directory_paths(): void
    {
        foreach ($this->invalidRelativePaths(false) as $type => [$relativePath, $invalid]) {
            $procedure = fn () => $this->directory()->subdirectory($relativePath);
            $exception = $invalid ? Exception\InvalidPath::class : Exception\UnreachablePath::class;
            $this->assertExceptionType($procedure, $exception, "Failed for `$type`");
        }
    }

    public function test_path_separator_normalization(): void
    {
        $root     = self::$temp->directory();
        $instance = $this->directory(self::$temp->directory('foo/bar'));
        $this->assertEquals($instance, $this->directory($root . '/foo/bar'));
        $this->assertEquals($instance, $this->directory(str_replace('/', '\\', $root) . '\foo\bar'));
        $this->assertEquals($instance, $this->directory($root . '\foo/bar/'));
        $this->assertEquals($instance, $this->directory($root . '/foo\bar\\'));

        $path     = self::$temp->name('bar/baz');
        $instance = $this->directory($root);
        $this->assertDirectory($instance, '/bar/baz');
        $this->assertDirectory($instance, 'bar/baz');
        $this->assertDirectory($instance, '\bar\baz');
        $this->assertDirectory($instance, '\\\\\\bar/baz\\');
        $this->assertFile($instance, '/bar/baz');
        $this->assertFile($instance, '\bar\baz');
        $this->assertFile($instance, 'bar/baz////');
        $this->assertFile($instance, '\bar/baz\\');
    }

    private function invalidDirectoryPaths(): array
    {
        chdir(self::$temp->directory());
        return [
            'file path'         => self::$temp->file('foo/bar/baz.txt'),
            'not existing path' => self::$temp->name('not/exists'),
            'invalid symlink'   => self::$temp->symlink('', 'link'),
            'valid symlink'     => self::$temp->symlink(self::$temp->name('foo/bar'), 'link'),
            'relative path'     => self::$temp->normalized('./foo/bar'),
            'step-up path'      => self::$temp->name('foo/bar/..'),
            'empty path'        => '',
            'dot path'          => '.'
        ];
    }

    private function invalidRelativePaths(bool $forFile): array
    {
        $directory = self::$temp->directory('foo/bar');
        $file      = self::$temp->file('foo/bar/baz.txt');
        self::$temp->symlink($file, 'file/name.lnk');
        self::$temp->symlink($directory, 'dir/name.lnk');
        self::$temp->symlink('', 'dead/name.lnk');

        return [
            'file <=> directory'      => [$forFile ? 'foo/bar' : 'foo/bar/baz.txt', false],
            'link file <=> directory' => [$forFile ? 'dir/name.lnk' : 'file/name.lnk', false],
            'file on path'            => ['foo/bar/baz.txt/file.or.dir', false],
            'file symlink on path'    => ['file/name.lnk/baz', false],
            'dead symlink'            => ['dead/name.lnk', false],
            'dead symlink on path'    => ['dead/name.lnk/baz', false],
            'empty segment'           => ['foo/bar//baz.txt', true],
            'dot segment'             => ['./foo/bar/baz', true],
            'double dot segment'      => ['foo/baz/../dir', true]
        ];
    }

    private function assertFile(LocalDirectory $rootDirectory, string $pathname): void
    {
        $expected = new LocalFile($rootDirectory, self::$temp->normalized($pathname));
        $this->assertEquals($expected, $rootDirectory->file($pathname));
    }

    private function assertDirectory(LocalDirectory $rootDirectory, string $pathname): void
    {
        $expected = $rootDirectory->pathname() . DIRECTORY_SEPARATOR . self::$temp->normalized($pathname);
        $this->assertSame($expected, $rootDirectory->subdirectory($pathname)->pathname());
    }

    private function assertExceptionType(callable $procedure, string $expectedException, string $fail): void
    {
        try {
            $procedure();
        } catch (Exception $exception) {
            $this->assertInstanceOf($expectedException, $exception, $fail);
            return;
        }

        $this->fail($fail);
    }

    private function directory(string $path = null): ?LocalDirectory
    {
        return LocalDirectory::instance($path ?? self::$temp->directory());
    }
}
