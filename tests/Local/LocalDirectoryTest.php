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

use Shudd3r\Filesystem\Local\LocalDirectory;
use Shudd3r\Filesystem\Local\LocalLink;
use Shudd3r\Filesystem\Local\LocalFile;
use Shudd3r\Filesystem\Node;
use Shudd3r\Filesystem\Exception;


class LocalDirectoryTest extends LocalFilesystemTests
{
    public function test_static_constructor_for_not_real_directory_path_returns_null(): void
    {
        foreach ($this->invalidRootPaths() as $type => $path) {
            $this->assertNull($this->directory($path), sprintf('Failed for `%s`', $type));
        }
    }

    public function test_static_constructor_for_existing_directory_path_returns_root_directory_instance(): void
    {
        $this->assertInstanceOf(LocalDirectory::class, $this->directory());

        $root = $this->directory($path = self::$temp->directory('foo/bar'));
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

    public function test_create_for_writable_path_creates_directory(): void
    {
        $directory = $this->directory()->subdirectory('foo');
        $this->assertDirectoryDoesNotExist($directory->pathname());
        $directory->create();
        $this->assertDirectoryExists($directory->pathname());
    }

    public function test_create_for_not_writable_path_throws_exception(): void
    {
        self::$temp->file('foo.file');
        $directory = $this->directory()->subdirectory('foo.file/bar');
        $this->assertExceptionType(Exception\UnexpectedLeafNode::class, fn () => $directory->create());
    }

    public function test_subdirectory_for_valid_path_returns_LocalDirectory(): void
    {
        $this->assertInstanceOf(LocalDirectory::class, $this->directory()->subdirectory('foo/bar'));
    }

    public function test_subdirectory_for_invalid_path_throws_Filesystem_Exception(): void
    {
        $procedure = fn () => $this->directory()->subdirectory('foo//bar');
        $this->assertExceptionType(Exception\InvalidNodeName::class, $procedure);
    }

    public function test_link_for_valid_path_returns_LocalLink(): void
    {
        $this->assertInstanceOf(LocalLink::class, $this->directory()->link('foo/bar'));
    }

    public function test_link_for_invalid_path_throws_Filesystem_Exception(): void
    {
        $procedure = fn () => $this->directory()->link('foo/bar/../bar');
        $this->assertExceptionType(Exception\InvalidNodeName::class, $procedure);
    }

    public function test_file_for_valid_path_returns_File(): void
    {
        $this->assertInstanceOf(LocalFile::class, $this->directory()->file('foo/file.txt'));
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
        self::$temp->symlink($expected['foo.txt'], 'file.lnk');
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

        $this->override('is_writable', false, $file);
        $this->assertExceptionType(Exception\FailedPermissionCheck::class, fn () => $root->file('foo/bar.txt'));
    }

    public function test_runtime_remove_directory_failures(): void
    {
        $removeDirectory = function (): void {
            self::$temp->directory('foo');
            self::$temp->file('foo/bar/baz.txt');
            self::$temp->directory('foo/bar/sub');
            $this->directory()->subdirectory('foo')->remove();
        };

        $exception = Exception\IOException\UnableToRemove::class;
        $this->assertIOException($exception, $removeDirectory, 'unlink', self::$temp->pathname('foo/bar/baz.txt'));
        $this->assertIOException($exception, $removeDirectory, 'rmdir', self::$temp->pathname('foo/bar/sub'));
        $this->assertIOException($exception, $removeDirectory, 'rmdir', self::$temp->pathname('foo'));
    }

    public function test_runtime_create_directory_failure(): void
    {
        $directory = $this->directory()->subdirectory('foo');
        $create    = fn () => $directory->create();
        $exception = Exception\IOException\UnableToCreate::class;
        $this->assertIOException($exception, $create, 'mkdir', $directory->pathname());
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

    private function invalidRootPaths(): array
    {
        chdir(self::$temp->directory());
        return [
            'file path'         => self::$temp->file('foo/bar/baz.txt'),
            'not existing path' => self::$temp->pathname('not/exists'),
            'invalid symlink'   => self::$temp->symlink('', 'link'),
            'valid symlink'     => self::$temp->symlink(self::$temp->pathname('foo/bar'), 'link'),
            'relative path'     => self::$temp->relative('./foo/bar'),
            'step-up path'      => self::$temp->pathname('foo/bar/..'),
            'empty path'        => '',
            'dot path'          => '.'
        ];
    }

    private function directory(string $path = null, int $flags = null): ?LocalDirectory
    {
        return LocalDirectory::root($path ?? self::$temp->directory(), $flags);
    }
}
