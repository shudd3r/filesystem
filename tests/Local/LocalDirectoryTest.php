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
use Shudd3r\Filesystem\Local\Pathname;
use Shudd3r\Filesystem\Exception;
use Shudd3r\Filesystem\Tests\Fixtures;


class LocalDirectoryTest extends TestCase
{
    use Fixtures\TempFilesHandling;

    public function test_static_constructor_creates_root_instance_for_valid_path(): void
    {
        $rootPathname = self::$temp->name('not/exists');
        $this->assertNull(LocalDirectory::root($rootPathname));

        $rootPathname = self::$temp->directory('foo/bar');
        $expected     = new LocalDirectory(Pathname\DirectoryName::root($rootPathname));
        $this->assertEquals($expected, LocalDirectory::root($rootPathname));
    }

    public function test_pathname_for_root_directory_returns_absolute_path_to_existing_directory(): void
    {
        $path = Pathname\DirectoryName::root(self::$temp->directory('foo/bar/baz'));
        $this->assertSame((string) $path, $this->directory($path)->pathname());
    }

    public function test_pathname_for_relative_directory_returns_absolute_path_to_not_existing_directory(): void
    {
        $path = Pathname\DirectoryName::root(self::$temp->directory('foo'))->directory('bar/baz');
        $this->assertSame((string) $path, $this->directory($path)->pathname());
    }

    public function test_subdirectory_for_valid_path_returns_Directory(): void
    {
        $root      = Pathname\DirectoryName::root(self::$temp->directory());
        $directory = $this->directory($root);
        $this->assertEquals(new LocalDirectory($root->directory('foo/bar')), $directory->subdirectory('foo/bar'));
    }

    public function test_subdirectory_for_invalid_path_throws_Filesystem_Exception(): void
    {
        self::$temp->file('foo/bar.txt');

        $root      = Pathname\DirectoryName::root(self::$temp->directory());
        $procedure = fn (string $name) => $this->directory($root)->subdirectory($name);
        $this->assertExceptionType(Exception\InvalidPath::class, $procedure, 'foo//bar');
        $this->assertExceptionType(Exception\UnreachablePath::class, $procedure, 'foo/bar.txt');
    }

    public function test_file_for_valid_path_returns_File(): void
    {
        $root      = Pathname\DirectoryName::root(self::$temp->directory());
        $directory = $this->directory($root);
        $this->assertEquals(new LocalFile($root->file('foo/file.txt')), $directory->file('foo/file.txt'));
    }

    public function test_file_for_invalid_path_throws_Filesystem_Exception(): void
    {
        self::$temp->directory('foo/bar.dir');

        $root      = Pathname\DirectoryName::root(self::$temp->directory());
        $procedure = fn (string $name) => $this->directory($root)->file($name);
        $this->assertExceptionType(Exception\InvalidPath::class, $procedure, '');
        $this->assertExceptionType(Exception\UnreachablePath::class, $procedure, 'foo/bar.dir');
    }

    public function test_files_returns_all_files_iterator(): void
    {
        $directory = $this->directory();
        $filenames = ['bar/baz.txt', 'foo/bar/file.txt', 'foo.txt'];
        $expected  = [];
        foreach ($filenames as $name) {
            self::$temp->file($name);
            $expected[] = $directory->file($name);
        }

        $this->assertEquals($expected, $directory->files()->list());
    }

    private function assertExceptionType(string $expectedException, callable $procedure, string $name): void
    {
        try {
            $procedure($name);
        } catch (Exception $exception) {
            $message = 'Unexpected Exception type - expected `%s` caught `%s`';
            $this->assertInstanceOf($expectedException, $exception, sprintf($message, $expectedException, $exception));
            return;
        }

        $this->fail(sprintf('No Exception thrown - expected `%s`', $expectedException));
    }

    private function directory(Pathname\DirectoryName $name = null): ?LocalDirectory
    {
        return $name ? new LocalDirectory($name) : LocalDirectory::root(self::$temp->directory());
    }
}
