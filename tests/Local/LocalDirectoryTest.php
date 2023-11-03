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

use Shudd3r\Filesystem\Tests\DirectoryContractTests;
use Shudd3r\Filesystem\Local\LocalDirectory;
use Shudd3r\Filesystem\Node;
use Shudd3r\Filesystem\Exception;


class LocalDirectoryTest extends LocalFilesystemTests
{
    use DirectoryContractTests;

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
        $directory = $this->root([])->subdirectory('foo');
        $create    = fn () => $directory->create();
        $exception = Exception\IOException\UnableToCreate::class;
        $this->assertIOException($exception, $create, 'mkdir', $directory->pathname());
    }
}
