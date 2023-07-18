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
use Shudd3r\Filesystem\Local\Pathname;
use Shudd3r\Filesystem\Local\LocalFile;
use Shudd3r\Filesystem\Files;
use Shudd3r\Filesystem\Exception;
use Shudd3r\Filesystem\Tests\Fixtures;


class LocalDirectoryTest extends TestCase
{
    use Fixtures\TempFilesHandling;

    public function test_static_constructor_for_not_real_directory_path_returns_null(): void
    {
        $path = self::$temp->name('not/exists');
        $this->assertNull(LocalDirectory::root($path));

        $path = self::$temp->file('foo/file.path');
        $this->assertNull(LocalDirectory::root($path));

        $path = self::$temp->symlink(self::$temp->directory('foo/bar'), 'dir.link');
        $this->assertNull(LocalDirectory::root($path));
    }

    public function test_static_constructor_for_existing_directory_path_returns_root_directory_instance(): void
    {
        $path = self::$temp->directory('foo/bar');
        $root = LocalDirectory::root($path);
        $this->assertEquals(new LocalDirectory(Pathname\DirectoryName::forRootPath($path)), $root);
        $this->assertSame($path, $root->pathname());
        $this->assertSame('', $root->name());
        $this->assertTrue($root->exists());
    }

    public function test_pathname_for_relative_directory_returns_absolute_path_to_not_existing_directory(): void
    {
        $path      = Pathname\DirectoryName::forRootPath(self::$temp->directory('foo'))->directory('bar/baz');
        $directory = $this->directory($path);
        $this->assertSame($path->absolute(), $directory->pathname());
        $this->assertSame($path->relative(), $directory->name());
        $this->assertFalse($directory->exists());
    }

    public function test_subdirectory_for_valid_path_returns_Directory(): void
    {
        $root      = Pathname\DirectoryName::forRootPath(self::$temp->directory());
        $directory = $this->directory($root);
        $this->assertEquals(new LocalDirectory($root->directory('foo/bar')), $directory->subdirectory('foo/bar'));
    }

    public function test_subdirectory_for_invalid_path_throws_Filesystem_Exception(): void
    {
        self::$temp->file('foo/bar.txt');

        $root      = Pathname\DirectoryName::forRootPath(self::$temp->directory());
        $procedure = fn (string $name) => $this->directory($root)->subdirectory($name);
        $this->assertExceptionType(Exception\InvalidPath::class, $procedure, 'foo//bar');
        $this->assertExceptionType(Exception\UnreachablePath::class, $procedure, 'foo/bar.txt');
    }

    public function test_file_for_valid_path_returns_File(): void
    {
        $root      = Pathname\DirectoryName::forRootPath(self::$temp->directory());
        $directory = $this->directory($root);
        $this->assertEquals(new LocalFile($root->file('foo/file.txt')), $directory->file('foo/file.txt'));
    }

    public function test_file_for_invalid_path_throws_Filesystem_Exception(): void
    {
        self::$temp->directory('foo/bar.dir');

        $root      = Pathname\DirectoryName::forRootPath(self::$temp->directory());
        $procedure = fn (string $name) => $this->directory($root)->file($name);
        $this->assertExceptionType(Exception\InvalidPath::class, $procedure, '');
        $this->assertExceptionType(Exception\UnreachablePath::class, $procedure, 'foo/bar.dir');
    }

    public function test_files_returns_all_files_iterator(): void
    {
        $directory = $this->directory();
        $expected  = $this->files(['bar/baz.txt', 'foo/bar/file.txt', 'foo.txt']);
        $this->assertFiles($expected, $directory->files());
    }

    public function test_files_will_iterate_over_currently_existing_files(): void
    {
        $directory = $this->directory();
        $expected  = $this->files(['bar/baz.txt', 'foo/bar/file.txt', 'foo.txt']);
        $files     = $directory->files();
        $this->assertFiles($expected, $files);

        $expected['bar.txt'] = self::$temp->file('bar.txt');
        self::$temp->remove(self::$temp->name('bar/baz.txt'));
        unset($expected[self::$temp->normalized('bar/baz.txt')]);
        $this->assertFiles($expected, $files);
    }

    public function test_file_names_from_subdirectory_are_relative_to_root_directory(): void
    {
        $root    = $this->directory();
        $dirname = self::$temp->normalized('foo/bar');

        $expected = self::$temp->normalized('foo/bar/baz/file.txt');
        $this->assertSame($expected, $root->subdirectory($dirname)->file('baz/file.txt')->name());

        $expected = $root->pathname() . DIRECTORY_SEPARATOR . $expected;
        $this->assertSame($expected, $root->subdirectory($dirname)->file('baz/file.txt')->pathname());

        $files    = $this->files(['foo/file.txt', 'foo/bar/file.txt', 'foo/bar/baz/file.txt', 'root.file']);
        $dirOnly  = fn (string $filename) => str_starts_with($filename, $dirname);
        $expected = array_filter($files, $dirOnly, ARRAY_FILTER_USE_KEY);
        $this->assertFiles($expected, $root->subdirectory($dirname)->files());
    }

    public function test_converting_subdirectory_to_root_directory(): void
    {
        $rootPath = Pathname\DirectoryName::forRootPath(self::$temp->directory('dir/foo'));
        $relative = $this->directory()->subdirectory('dir/foo');

        $newRoot = $relative->asRoot();
        $this->assertSame($relative->pathname(), $newRoot->pathname());

        $this->assertSame(self::$temp->normalized('dir/foo'), $relative->name());
        $this->assertSame('', $newRoot->name());

        $this->assertSame(self::$temp->normalized('dir/foo/file.txt'), $relative->file('file.txt')->name());
        $this->assertSame('file.txt', $newRoot->file('file.txt')->name());

        $this->assertEquals($this->directory($rootPath), $newRoot);
        $this->assertSame($newRoot, $newRoot->asRoot());
    }

    public function test_converting_not_existing_subdirectory_to_root_throws_exception(): void
    {
        $relative = $this->directory()->subdirectory('dir/foo');
        $this->expectException(Exception\DirectoryDoesNotExist::class);
        $relative->asRoot();
    }

    public function test_remove_method(): void
    {
        self::$temp->symlink(self::$temp->file('foo/bar/baz.txt'), 'foo/link.file');
        self::$temp->symlink(self::$temp->directory('foo/bar/dir/sub'), 'foo/bar/sub.link');
        $this->directory()->subdirectory('foo')->remove();
        $this->assertFalse(is_dir(self::$temp->name('foo')));
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

    private function assertFiles(array $files, Files $fileIterator): void
    {
        /** @var LocalFile $file */
        foreach ($fileIterator as $file) {
            $name = $file->name();
            $this->assertTrue($file->exists(), sprintf('File `%s` should exist', $name));
            $this->assertArrayHasKey($name, $files, sprintf('Unexpected file `%s` found', $name));
            $this->assertSame($files[$name], $file->pathname());
            unset($files[$name]);
        }
        $this->assertSame([], $files, 'Some expected files were not found');
    }

    private function files(array $filenames): array
    {
        $files = [];
        foreach ($filenames as &$name) {
            $name = self::$temp->normalized($name);
            $files[$name] = self::$temp->file($name);
        }
        return $files;
    }

    private function directory(Pathname\DirectoryName $name = null): ?LocalDirectory
    {
        return $name ? new LocalDirectory($name) : LocalDirectory::root(self::$temp->directory());
    }
}
