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
use Shudd3r\Filesystem\Local\Pathname;
use Shudd3r\Filesystem\Exception;
use Shudd3r\Filesystem\Tests\Fixtures;


class PathnameTest extends TestCase
{
    use Fixtures\TempFilesHandling;

    public function test_instance_can_only_be_created_with_real_directory_path(): void
    {
        foreach ($this->invalidInstancePaths() as $type => $path) {
            $this->assertNull($this->path($path), "Failed for `$type`");
        }

        $path = self::$temp->directory('existing/directory');
        $this->assertSame($path, realpath($path));
        $this->assertTrue(is_dir($path));
        $this->assertInstanceOf(Pathname\DirectoryName::class, $this->path($path));
    }

    public function test_file_for_existing_pathname_returns_FileName(): void
    {
        self::$temp->file('foo/bar.txt');
        $this->assertFileName('foo/bar.txt');
    }

    public function test_file_for_not_existing_pathname_returns_FileName(): void
    {
        $this->assertFileName('foo/bar.txt');
    }

    public function test_file_for_linked_path_returns_FileName(): void
    {
        self::$temp->symlink(self::$temp->file('foo/bar.txt'), 'link/file.lnk');
        $this->assertFileName('link\file.lnk');

        self::$temp->symlink(self::$temp->directory('foo'), 'path.lnk');
        $this->assertFileName('path.lnk/bar.txt');
        $this->assertFileName('path.lnk/possible.file');
    }

    public function test_file_for_invalid_or_colliding_name_throws_FilesystemException(): void
    {
        foreach ($this->invalidNames(true) as $type => [$relativePath, $collision]) {
            $procedure = fn () => $this->path()->file($relativePath);
            $exception = $collision ? Exception\UnreachablePath::class : Exception\InvalidPath::class;
            $this->assertExceptionType($exception, $procedure, "Failed for `$type`");
        }
    }

    public function test_directory_for_existing_pathname_returns_DirectoryName(): void
    {
        self::$temp->directory('foo/bar/baz');
        $this->assertDirectoryName('foo/bar/baz');
    }

    public function test_directory_for_not_existing_pathname_returns_DirectoryName(): void
    {
        $this->assertDirectoryName('foo/bar/baz');
    }

    public function test_directory_for_linked_path_returns_DirectoryName(): void
    {
        self::$temp->symlink(self::$temp->directory('foo/bar/baz'), 'link/dir.lnk');
        $this->assertDirectoryName('link/dir.lnk');

        self::$temp->symlink(self::$temp->directory('foo/bar'), 'path.lnk');
        $this->assertDirectoryName('path.lnk/baz');
        $this->assertDirectoryName('path.lnk/possible.dir');
    }

    public function test_directory_for_invalid_or_colliding_name_throws_FilesystemException(): void
    {
        foreach ($this->invalidNames(false) as $type => [$name, $collision]) {
            $procedure = fn () => $this->path()->directory($name);
            $exception = $collision ? Exception\UnreachablePath::class : Exception\InvalidPath::class;
            $this->assertExceptionType($exception, $procedure, "Failed for `$type`");
        }
    }

    public function test_converting_relative_name_to_root_returns_root_directory_name(): void
    {
        $path = self::$temp->directory('foo/bar');
        $this->assertEquals($this->path($path), $newRoot = $this->path()->directory('foo/bar')->asRoot());
        $this->assertSame($newRoot, $newRoot->asRoot());
    }

    public function test_converting_relative_name_for_not_existing_directory_throws_exception(): void
    {
        $directory = $this->path()->directory('foo/bar');
        $this->expectException(Exception\DirectoryDoesNotExist::class);
        $directory->asRoot();
    }

    public function test_path_separator_normalization(): void
    {
        $rootName = self::$temp->directory();
        $expected = self::$temp->directory('foo/bar');
        $this->assertEquals($expected, $this->path($rootName . '/foo/bar')->absolute());
        $this->assertEquals($expected, $this->path(str_replace('/', '\\', $rootName) . '\foo\bar')->absolute());
        $this->assertEquals($expected, $this->path($rootName . '\foo/bar/')->absolute());
        $this->assertEquals($expected, $this->path($rootName . '/foo\bar\\')->absolute());

        $this->assertNormalizedName('/bar/baz');
        $this->assertNormalizedName('bar/baz');
        $this->assertNormalizedName('\bar\baz');
        $this->assertNormalizedName('\\\\\\bar/baz\\');
        $this->assertNormalizedName('\bar/baz\\');
    }

    private function invalidInstancePaths(): array
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

    private function invalidNames(bool $forFile): array
    {
        $directory = self::$temp->directory('foo/bar');
        $file      = self::$temp->file('foo/bar/baz.txt');
        self::$temp->symlink($file, 'file/name.lnk');
        self::$temp->symlink($directory, 'dir/name.lnk');
        self::$temp->symlink('', 'dead/name.lnk');

        return [
            'empty name'              => ['', false],
            'resolved empty'          => ['//\\', false],
            'empty segment'           => ['foo/bar//baz.txt', false],
            'dot segment'             => ['./foo/bar/baz', false],
            'double dot segment'      => ['foo/baz/../dir', false],
            'file <=> directory'      => [$forFile ? 'foo/bar' : 'foo/bar/baz.txt', true],
            'link file <=> directory' => [$forFile ? 'dir/name.lnk' : 'file/name.lnk', true],
            'file on path'            => ['foo/bar/baz.txt/file.or.dir', true],
            'file symlink on path'    => ['file/name.lnk/baz', true],
            'dead symlink'            => ['dead/name.lnk', true],
            'dead symlink on path'    => ['dead/name.lnk/baz', true]
        ];
    }

    private function assertNormalizedName(string $name): void
    {
        $instance     = $this->path();
        $expectedPath = self::$temp->name($name);
        $expectedName = self::$temp->normalized($name);
        $this->assertSame($expectedPath, $instance->directory($name)->absolute());
        $this->assertSame($expectedPath, $instance->file($name)->absolute());
        $this->assertSame($expectedName, $instance->file($name)->relative());
    }

    private function assertDirectoryName(string $name): void
    {
        $this->assertInstanceOf(Pathname\DirectoryName::class, $path = $this->path()->directory($name));
        $this->assertSame(self::$temp->name($name), $path->absolute());
        $this->assertSame(self::$temp->normalized($name), $path->relative());
    }

    private function assertFileName(string $name): void
    {
        $this->assertInstanceOf(Pathname\FileName::class, $path = $this->path()->file($name));
        $this->assertSame(self::$temp->name($name), $path->absolute());
    }

    private function assertExceptionType(string $expectedException, callable $procedure, string $fail): void
    {
        try {
            $procedure();
        } catch (Exception $exception) {
            $this->assertInstanceOf($expectedException, $exception, $fail);
            return;
        }

        $this->fail($fail);
    }

    private function path(string $pathname = null): ?Pathname\DirectoryName
    {
        return Pathname\DirectoryName::forRootPath($pathname ?? self::$temp->directory());
    }
}
