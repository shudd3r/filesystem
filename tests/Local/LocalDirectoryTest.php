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
use Shudd3r\Filesystem\Local\LocalLink;
use Shudd3r\Filesystem\Local\LocalFile;
use Shudd3r\Filesystem\Local\Pathname;
use Shudd3r\Filesystem\Generic\FileIterator;
use Shudd3r\Filesystem\Node;
use Shudd3r\Filesystem\Exception;
use Shudd3r\Filesystem\Tests\Fixtures;


class LocalDirectoryTest extends TestCase
{
    use Fixtures\TempFilesHandling;
    use Fixtures\ExceptionAssertions;

    public function test_static_constructor_for_not_real_directory_path_returns_null(): void
    {
        $path = self::$temp->pathname('not/exists');
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

    public function test_exists_for_existing_directory_returns_true(): void
    {
        self::$temp->symlink(self::$temp->directory('foo/bar/baz.dir'), 'dir.lnk');
        $root = $this->directory();
        $this->assertTrue($root->exists());
        $this->assertTrue($root->subdirectory('foo/bar/baz.dir')->exists());
        $this->assertTrue($root->subdirectory('dir.lnk')->exists());
    }

    public function test_exists_for_not_existing_directory_returns_false(): void
    {
        self::$temp->symlink(self::$temp->file('foo/bar/baz.file'), 'file.lnk');
        $root = $this->directory();
        $this->assertFalse($root->subdirectory('foo/bar/baz.dir')->exists());
        $this->assertFalse($root->subdirectory('foo/bar/baz.file')->exists());
        $this->assertFalse($root->subdirectory('file.lnk')->exists());
    }

    public function test_subdirectory_for_valid_path_returns_LocalDirectory(): void
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

    public function test_link_for_valid_path_returns_LocalLink(): void
    {
        $directory = $this->directory();
        $this->assertInstanceOf(LocalLink::class, $directory->link('foo/bar'));
    }

    public function test_link_for_invalid_path_throws_Filesystem_Exception(): void
    {
        $procedure = fn () => $this->directory()->link('foo/bar/../bar');
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
        self::$temp->remove(self::$temp->pathname('bar/baz.txt'));
        unset($expected['bar/baz.txt']);
        $this->assertFiles($expected, $files);
    }

    public function test_file_names_from_subdirectory_are_relative_to_root_directory(): void
    {
        $root    = $this->directory();
        $dirname = 'foo/bar';

        $expected = 'foo/bar/baz/file.txt';
        $this->assertSame($expected, $root->subdirectory($dirname)->file('baz/file.txt')->name());

        $expected = $root->pathname() . DIRECTORY_SEPARATOR . self::$temp->relative($expected);
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

        $this->assertSame('dir/foo', $relative->name());
        $this->assertSame('', $newRoot->name());

        $this->assertSame('dir/foo/file.txt', $relative->file('file.txt')->name());
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

    public function test_remove_method_deletes_existing_structure(): void
    {
        self::$temp->symlink(self::$temp->file('foo/bar/baz.txt'), 'foo/link.file');
        self::$temp->symlink(self::$temp->directory('foo/bar/dir/sub'), 'foo/bar/sub.link');
        $this->directory()->subdirectory('foo')->remove();
        $this->assertDirectoryDoesNotExist(self::$temp->pathname('foo'));
    }

    public function test_root_instantiated_with_assert_flags_throws_exceptions_for_derived_nodes(): void
    {
        $file = self::$temp->file('foo/bar.txt');

        $root = $this->directory(null, Node::PATH);
        $this->assertExceptionType(Exception\UnexpectedNodeType::class, fn () => $root->subdirectory('foo/bar.txt'));
        $this->assertExceptionType(Exception\UnexpectedLeafNode::class, fn () => $root->file('foo/bar.txt/file.txt'));
        $this->assertInstanceOf(Node::class, $root->file('foo.file'));

        $root = $this->directory(null, Node::EXISTS | Node::WRITE);
        $this->assertExceptionType(Exception\NodeNotFound::class, fn () => $root->file('foo.file'));
        $this->assertInstanceOf(Node::class, $root->file('foo/bar.txt'));

        self::override('is_writable', false, $file);
        $this->assertExceptionType(Exception\FailedPermissionCheck::class, fn () => $root->file('foo/bar.txt'));
    }

    public function test_runtime_remove_directory_failures(): void
    {
        $dirNode   = self::$temp->directory('foo');
        $fileNode  = self::$temp->file('foo/bar/baz.txt');
        $subNode   = self::$temp->directory('foo/bar/sub');
        $directory = $this->directory()->subdirectory('foo');
        $remove    = fn () => $directory->remove();

        $this->assertIOException(Exception\IOException\UnableToRemove::class, $remove, 'unlink', $fileNode);
        $this->assertIOException(Exception\IOException\UnableToRemove::class, $remove, 'rmdir', $subNode);
        $this->assertIOException(Exception\IOException\UnableToRemove::class, $remove, 'rmdir', $dirNode);
    }

    private function assertFiles(array $files, FileIterator $fileIterator): void
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
            $name = trim(str_replace('\\', '/', $name), '/');
            $files[$name] = self::$temp->file($name);
        }
        return $files;
    }

    private function directory(string $name = null, int $flags = null): ?LocalDirectory
    {
        return LocalDirectory::root($name ?? self::$temp->directory(), $flags);
    }
}
