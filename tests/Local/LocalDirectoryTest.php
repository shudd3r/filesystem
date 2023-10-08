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
            $this->assertNull(LocalDirectory::root($path), sprintf('Failed for `%s`', $type));
        }
    }

    public function test_static_constructor_for_existing_directory_path_returns_root_directory_instance(): void
    {
        $this->assertInstanceOf(LocalDirectory::class, $this->root(['foo' => ['bar' => []]]));

        $root = LocalDirectory::root($path = $this->path('foo/bar'));
        $this->assertSame($path, $root->pathname());
        $this->assertSame('', $root->name());
        $this->assertTrue($root->exists());
    }

    public function test_subdirectory_for_valid_path_returns_Directory_with_descendant_path(): void
    {
        $subdirectory = $this->root()->subdirectory('foo/bar');
        $this->assertInstanceOf(LocalDirectory::class, $subdirectory);
        $this->assertSame($this->path('foo/bar'), $subdirectory->pathname());
        $this->assertSame('foo/bar', $subdirectory->name());
        $this->assertSame($this->path('foo/bar/baz'), $subdirectory->subdirectory('baz')->pathname());
    }

    public function test_subdirectory_for_invalid_path_throws_Filesystem_Exception(): void
    {
        $procedure = fn () => $this->root()->subdirectory('foo//bar');
        $this->assertExceptionType(Exception\InvalidNodeName::class, $procedure);
    }

    public function test_file_for_valid_path_returns_File_with_descendant_path(): void
    {
        $file = $this->root()->file('foo/file.txt');
        $this->assertInstanceOf(LocalFile::class, $file);
        $this->assertSame($this->path('foo/file.txt'), $file->pathname());
        $this->assertSame('foo/file.txt', $file->name());
    }

    public function test_file_for_invalid_path_throws_Filesystem_Exception(): void
    {
        $procedure = fn () => $this->root()->file('');
        $this->assertExceptionType(Exception\InvalidNodeName::class, $procedure);
    }

    public function test_link_for_valid_path_returns_Link_with_descendant_path(): void
    {
        $link = $this->root()->link('foo/bar');
        $this->assertInstanceOf(LocalLink::class, $link);
        $this->assertSame($this->path('foo/bar'), $link->pathname());
        $this->assertSame('foo/bar', $link->name());
    }

    public function test_link_for_invalid_path_throws_Filesystem_Exception(): void
    {
        $procedure = fn () => $this->root()->link('foo/bar/../bar');
        $this->assertExceptionType(Exception\InvalidNodeName::class, $procedure);
    }

    public function test_exists_for_existing_directory_returns_true(): void
    {
        $root = $this->root(['foo' => ['bar' => ['baz.dir' => []]], 'dir.lnk' => 'foo/bar/baz.dir']);
        $this->assertTrue($root->exists());
        $this->assertTrue($root->subdirectory('foo/bar/baz.dir')->exists());
        $this->assertTrue($root->subdirectory('dir.lnk')->exists());
    }

    public function test_exists_for_not_existing_directory_returns_false(): void
    {
        $root = $this->root(['foo' => ['bar' => ['baz.file' => '']], 'file.lnk' => 'foo/bar/baz.file']);
        $this->assertFalse($root->subdirectory('foo/bar/baz.dir')->exists());
        $this->assertFalse($root->subdirectory('foo/bar/baz.file')->exists());
        $this->assertFalse($root->subdirectory('file.lnk')->exists());
    }

    public function test_create_for_writable_path_creates_directory(): void
    {
        $directory = $this->root()->subdirectory('foo');
        $this->assertDirectoryDoesNotExist($directory->pathname());
        $directory->create();
        $this->assertDirectoryExists($directory->pathname());
    }

    public function test_create_for_not_writable_path_throws_exception(): void
    {
        $directory = $this->root(['foo.file' => ''])->subdirectory('foo.file/bar');
        $this->assertExceptionType(Exception\UnexpectedLeafNode::class, fn () => $directory->create());
    }

    public function test_converting_existing_subdirectory_to_root_directory(): void
    {
        $subdirectory = $this->root(['foo' => ['bar' => []]])->subdirectory('foo/bar');
        $newRoot      = $subdirectory->asRoot();

        $this->assertSame($subdirectory->pathname(), $newRoot->pathname());

        $this->assertSame('foo/bar', $subdirectory->name());
        $this->assertSame('', $newRoot->name());

        $this->assertSame('foo/bar/baz.txt', $subdirectory->file('baz.txt')->name());
        $this->assertSame('baz.txt', $newRoot->file('baz.txt')->name());
    }

    public function test_converting_not_existing_subdirectory_to_root_throws_exception(): void
    {
        $relative = $this->root()->subdirectory('dir/foo');
        $this->expectException(Exception\RootDirectoryNotFound::class);
        $relative->asRoot();
    }

    public function test_files_returns_all_files_iterator(): void
    {
        $root = $this->root($this->exampleStructure(['foo' => ['fizz' => '', 'buzz' => '']]));

        $expected = $this->files(['bar.txt', 'foo/bar/baz.txt', 'foo/buzz', 'foo/fizz'], $root);
        $this->assertFiles($expected, $root->files());

        $expected = $this->files(['foo/bar/baz.txt', 'foo/buzz', 'foo/fizz'], $root);
        $this->assertFiles($expected, $root->subdirectory('foo')->files());

        $directory = $root->subdirectory('foo')->asRoot();
        $expected  = $this->files(['bar/baz.txt', 'buzz', 'fizz'], $directory);
        $this->assertFiles($expected, $directory->files());
    }

    public function test_files_will_iterate_over_currently_existing_files(): void
    {
        $directory = $this->root($this->exampleStructure(['foo' => ['fizz' => '', 'buzz' => '']]));

        $files = $directory->files();
        $directory->subdirectory('foo/bar')->remove();
        $directory->file('bar.txt')->remove();

        $expected = $this->files(['foo/buzz', 'foo/fizz'], $directory);
        $this->assertFiles($expected, $files);
    }

    public function test_remove_method_deletes_existing_structure(): void
    {
        $root = $this->root([
            'foo'     => ['bar' => ['baz.txt' => '', 'dir' => []], 'file.lnk' => 'foo/bar/baz.txt'],
            'dir.lnk' => 'foo/bar/dir',
            'bar'     => []
        ]);
        $this->assertDirectoryExists($link = $this->path('dir.lnk'));
        $root->subdirectory('foo')->remove();
        $this->assertDirectoryDoesNotExist($this->path('foo'));
        $this->assertDirectoryExists($this->path('bar'));
        $this->assertDirectoryDoesNotExist($link);
        $this->assertTrue(is_link($link));
    }

    public function test_remove_method_for_not_existing_directory_is_ignored(): void
    {
        $this->root(['bar.txt' => ''])->subdirectory('bar.txt')->remove();
        $this->assertFileExists($this->path('bar.txt'));
    }

    public function test_root_instantiated_with_assert_flags_throws_exceptions_for_derived_nodes(): void
    {
        $this->root(['foo' => ['bar.txt' => '']]);

        $root = LocalDirectory::root($this->path(), Node::PATH);
        $this->assertExceptionType(Exception\UnexpectedNodeType::class, fn () => $root->subdirectory('foo/bar.txt'));
        $this->assertExceptionType(Exception\UnexpectedLeafNode::class, fn () => $root->file('foo/bar.txt/file.txt'));
        $this->assertInstanceOf(Node::class, $root->file('foo.file'));

        $root = LocalDirectory::root($this->path(), Node::EXISTS | Node::WRITE);
        $this->assertExceptionType(Exception\NodeNotFound::class, fn () => $root->file('foo.file'));
        $this->assertInstanceOf(Node::class, $root->file('foo/bar.txt'));

        $this->override('is_writable', false, $this->path('foo/bar.txt'));
        $this->assertExceptionType(Exception\FailedPermissionCheck::class, fn () => $root->file('foo/bar.txt'));
    }

    public function test_runtime_remove_directory_failures(): void
    {
        $remove = function (): void {
            $this->root(['foo' => ['bar' => ['baz.txt' => '', 'sub' => []]]])->subdirectory('foo')->remove();
        };

        $exception = Exception\IOException\UnableToRemove::class;
        $this->assertIOException($exception, $remove, 'unlink', $this->path('foo/bar/baz.txt'));
        $this->assertIOException($exception, $remove, 'rmdir', $this->path('foo/bar/sub'));
        $this->assertIOException($exception, $remove, 'rmdir', $this->path('foo'));
    }

    public function test_runtime_create_directory_failure(): void
    {
        $directory = $this->root()->subdirectory('foo');
        $create    = fn () => $directory->create();
        $exception = Exception\IOException\UnableToCreate::class;
        $this->assertIOException($exception, $create, 'mkdir', $directory->pathname());
    }
}
