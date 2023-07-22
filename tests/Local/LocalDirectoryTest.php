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
    use Fixtures\ExceptionAssertion;

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
        $this->assertEquals(new LocalDirectory(Pathname::root($path)), $root);
        $this->assertSame($path, $root->pathname());
        $this->assertSame('', $root->name());
        $this->assertTrue($root->exists());
    }

    public function test_pathname_for_relative_directory_returns_absolute_path_to_not_existing_directory(): void
    {
        $path      = Pathname::root(self::$temp->directory('foo'))->forChildNode('bar/baz');
        $directory = new LocalDirectory($path);
        $this->assertSame($path->absolute(), $directory->pathname());
        $this->assertSame($path->relative(), $directory->name());
        $this->assertFalse($directory->exists());
    }

    public function test_subdirectory_for_valid_path_returns_Directory(): void
    {
        $root      = Pathname::root(self::$temp->directory());
        $directory = new LocalDirectory($root);
        $this->assertEquals(new LocalDirectory($root->forChildNode('foo/bar')), $directory->subdirectory('foo/bar'));
    }

    public function test_subdirectory_for_invalid_path_throws_Filesystem_Exception(): void
    {
        $procedure = fn () => $this->directory()->subdirectory('foo//bar');
        $this->assertExceptionType(Exception\InvalidNodeName::class, $procedure);
    }

    public function test_file_for_valid_path_returns_File(): void
    {
        $root      = Pathname::root(self::$temp->directory());
        $directory = new LocalDirectory($root);
        $this->assertEquals(new LocalFile($root->forChildNode('foo/file.txt')), $directory->file('foo/file.txt'));
    }

    public function test_file_for_invalid_path_throws_Filesystem_Exception(): void
    {
        $procedure = fn () => $this->directory()->file('');
        $this->assertExceptionType(Exception\InvalidNodeName::class, $procedure);
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
        $rootPath = self::$temp->directory('dir/foo');
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
        $this->expectException(Exception\RootDirectoryNotFound::class);
        $relative->asRoot();
    }

    public function test_readable_and_writable_status_for_existing_directory(): void
    {
        $pathname = self::$temp->directory('foo');

        $directory = $this->directory($pathname);
        $this->assertTrue($directory->isReadable());
        $this->assertTrue($directory->isWritable());
        self::override('is_readable', $pathname, false);
        $this->assertFalse($directory->isReadable());
        $this->assertTrue($directory->isWritable());
        self::override('is_writable', $pathname, false);
        $this->assertFalse($directory->isReadable());
        $this->assertFalse($directory->isWritable());
    }

    public function test_readable_and_writable_status_for_not_existing_directories_depends_on_ancestor_permissions(): void
    {
        $existing = self::$temp->directory('foo');

        $directory = $this->directory($existing)->subdirectory('bar/dir');
        $this->assertTrue($directory->isReadable());
        $this->assertTrue($directory->isWritable());
        self::override('is_readable', $existing, false);
        $this->assertFalse($directory->isReadable());
        $this->assertTrue($directory->isWritable());
        self::override('is_writable', $existing, false);
        $this->assertFalse($directory->isReadable());
        $this->assertFalse($directory->isWritable());
    }

    public function test_readable_and_writable_status_for_invalid_file_path_returns_false(): void
    {
        self::$temp->file('foo/file');

        $directory = $this->directory()->subdirectory('foo/file/baz');
        $this->assertFalse($directory->isReadable());
        $this->assertFalse($directory->isWritable());
    }

    public function test_instance_validation_for_unreachable_paths(): void
    {
        $file = self::$temp->file('foo/bar.file');
        self::$temp->symlink($file, 'file.link');
        self::$temp->symlink('', 'foo/dead.link');

        $unreachablePaths = [
            'foo/bar.file'       => Exception\UnexpectedNodeType::class,
            'foo/bar.file/path'  => Exception\UnexpectedLeafNode::class,
            'file.link'          => Exception\UnexpectedNodeType::class,
            'file.link/path'     => Exception\UnexpectedLeafNode::class,
            'foo/dead.link'      => Exception\UnexpectedNodeType::class,
            'foo/dead.link/path' => Exception\UnexpectedLeafNode::class
        ];

        $directory = $this->directory();
        foreach ($unreachablePaths as $name => $expectedException) {
            $check = fn () => $directory->subdirectory($name)->validated();
            $this->assertExceptionType($expectedException, $check, $name);
        }
    }

    public function test_remove_method(): void
    {
        self::$temp->symlink(self::$temp->file('foo/bar/baz.txt'), 'foo/link.file');
        self::$temp->symlink(self::$temp->directory('foo/bar/dir/sub'), 'foo/bar/sub.link');
        $this->directory()->subdirectory('foo')->remove();
        $this->assertFalse(is_dir(self::$temp->name('foo')));
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

    private function directory(string $name = null): ?LocalDirectory
    {
        return LocalDirectory::root($name ?? self::$temp->directory());
    }
}
