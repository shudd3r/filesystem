<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Tests\Virtual;

use Shudd3r\Filesystem\Virtual\VirtualNode;
use Shudd3r\Filesystem\Node;
use Shudd3r\Filesystem\Exception;
use Shudd3r\Filesystem\Tests\Doubles;


class VirtualFilesystemTest extends VirtualFilesystemTests
{
    public function test_exists_method(): void
    {
        $this->assertExists($this->directory());
        $this->assertExists($this->file('bar.txt'));
        $this->assertNotExists($this->directory('bar.txt'));
        $this->assertExists($this->directory('foo/bar'));
        $this->assertExists($this->file('foo/bar/baz.txt'));
        $this->assertNotExists($this->link('foo/bar/baz.txt'));
        $this->assertExists($this->link('foo/file.lnk'));
        $this->assertExists($this->file('foo/file.lnk'));
        $this->assertExists($this->link('dir.lnk'));
        $this->assertExists($this->directory('dir.lnk'));
        $this->assertExists($this->file('dir.lnk/baz.txt'));
        $this->assertNotExists($this->file('dir.lnk/link'));
        $this->assertNotExists($this->directory('foo/bar/baz/directory'));
        $this->assertNotExists($this->directory('foo/lnk/directory'));
        $this->assertExists($this->link('inv.lnk'));
        $this->assertNotExists($this->file('inv.lnk'));
    }

    public function test_remove_not_existing_node_is_ignored(): void
    {
        $expectedRoot = $this->directory('', $this->exampleStructure());
        $this->file('foo/bar/baz/file.txt')->remove();
        $this->assertEquals($expectedRoot, $this->root);

        $this->directory('foo/bar/baz.txt')->remove();
        $this->assertEquals($expectedRoot, $this->root);
    }

    public function test_remove_existing_node_removes_node(): void
    {
        $file = $this->file('foo/bar/baz.txt');
        $file->remove();
        $this->assertNotExists($file);

        $link = $this->link('foo/lnk');
        $link->remove();
        $this->assertNotExists($link);
        $this->assertExists($this->file('bar.txt'));

        $directory    = $this->directory('foo');
        $subdirectory = $directory->subdirectory('bar');
        $this->assertExists($subdirectory);
        $directory->remove();
        $this->assertNotExists($subdirectory);
        $this->assertNotExists($directory);
    }

    public function test_remove_linked_node_removes_link(): void
    {
        $file = $this->file('foo/lnk');
        $file->remove();
        $this->assertNotExists($file);
        $this->assertNotExists($this->link('foo/lnk'));
        $this->assertExists($this->file('bar.txt'));

        $directory = $this->directory('bar.lnk');
        $directory->remove();
        $this->assertNotExists($directory);
        $this->assertNotExists($this->link('bar.lnk'));
        $this->assertExists($this->directory('foo/bar'));

        $file = $this->file('inv.lnk');
        $file->remove();
        $this->assertExists($this->link('inv.lnk'));
    }

    public function test_node_permissions(): void
    {
        $file = $this->file('foo/bar/baz.txt');
        $this->assertTrue($file->isReadable());
        $this->assertTrue($file->isWritable());
        $this->assertTrue($file->isRemovable());
    }

    public function test_directory_methods(): void
    {
        $directory = $this->directory();
        $this->assertSame('vfs://', $directory->pathname());
        $this->assertSame('', $directory->name());

        $directory = $this->directory('foo');
        $this->assertSame('vfs://foo', $directory->pathname());
        $this->assertSame('foo', $directory->name());
        $subdirectory = $directory->subdirectory('bar/baz');
        $this->assertSame('foo/bar/baz', $subdirectory->name());

        $directory = $directory->asRoot();
        $this->assertSame('', $directory->name());
        $this->assertExists($directory->file('bar/baz.txt'));
        $this->assertExists($directory->link('file.lnk'));

        $validatePath = fn () => $directory->subdirectory('bar/baz.txt/dir')->validated();
        $this->assertExceptionType(Exception\UnexpectedLeafNode::class, $validatePath);

        $validatePath = fn () => $directory->subdirectory('bar/baz.txt')->validated();
        $this->assertExceptionType(Exception\UnexpectedNodeType::class, $validatePath);

        $subdirectory = $directory->subdirectory('bar/baz');
        $this->assertSame('bar/baz', $subdirectory->name());
        $this->assertSame('vfs://foo/bar/baz', $subdirectory->pathname());
        $this->assertSame($subdirectory, $subdirectory->validated(Node::PATH | Node::READ | Node::WRITE));

        $validateExisting = fn () => $subdirectory->validated(Node::EXISTS);
        $this->assertExceptionType(Exception\NodeNotFound::class, $validateExisting);

        $notExistingRoot = fn () => $subdirectory->asRoot();
        $this->assertExceptionType(Exception\RootDirectoryNotFound::class, $notExistingRoot);

        $subdirectory->create();
        $this->assertTrue($subdirectory->exists());
    }

    public function test_directory_file_iteration(): void
    {
        $directory = $this->directory();
        $expected  = ['bar.txt' => 'vfs://bar.txt', 'foo/bar/baz.txt' => 'vfs://foo/bar/baz.txt'];
        $this->assertFiles($expected, $directory->files());

        $directory = $directory->subdirectory('foo');
        $expected  = ['foo/bar/baz.txt' => 'vfs://foo/bar/baz.txt'];
        $this->assertFiles($expected, $directory->files());

        $expected = ['bar/baz.txt' => 'vfs://foo/bar/baz.txt'];
        $this->assertFiles($expected, $directory->asRoot()->files());

        $directory = $this->directory('foo/empty');
        $this->assertFiles([], $directory->files());

        $directory = $this->directory('foo/not-exists');
        $this->assertFiles([], $directory->files());
    }

    public function test_file_contents(): void
    {
        $this->assertSame('baz contents', $this->file('foo/bar/baz.txt')->contents());
        $this->assertSame('bar contents', $this->file('foo/file.lnk')->contents());

        $this->assertNull($this->file('foo/bar/baz.txt')->contentStream());

        $file = $this->file('foo/file.lnk/bar.txt');
        $this->assertExceptionType(Exception\UnexpectedLeafNode::class, fn () => $file->contents(), 'invalid path');

        $file = $this->file('foo');
        $this->assertExceptionType(Exception\UnexpectedNodeType::class, fn () => $file->contents(), 'invalid type');
    }

    public function test_file_write(): void
    {
        $file = $this->file('baz/file.txt');
        $this->assertSame('', $file->contents());

        $file->write('contents');
        $this->assertSame('contents', $file->contents());

        $file->append('-appended');
        $this->assertSame('contents-appended', $file->contents());

        $file->writeStream($this->stream('stream contents'));
        $this->assertSame('stream contents', $file->contents());

        $file->copy($this->file('foo/file.lnk'));
        $this->assertSame('bar contents', $file->contents());

        $file->moveTo($this->directory('bar'));
        $this->assertFalse($file->exists());
        $this->assertSame('bar contents', $this->file('bar/file.txt')->contents());
    }

    public function test_link_target(): void
    {
        $link = $this->link('foo/file.lnk/bar.txt');
        $this->assertExceptionType(Exception\UnexpectedLeafNode::class, fn () => $link->target(), 'invalid path');

        $link = $this->link('foo/file.lnk');
        $this->assertSame('vfs://bar.txt', $link->target());
        $this->file('bar.txt')->remove();
        $this->assertNull($link->target());
        $this->assertSame('vfs://bar.txt', $link->target(true));

        $link = $this->link('foo');
        $this->assertExceptionType(Exception\UnexpectedNodeType::class, fn () => $link->target(), 'invalid type');
    }

    public function test_link_setTarget(): void
    {
        $link = $this->link('foo/file.lnk');
        $link->setTarget($this->file('foo/bar/baz.txt'));
        $this->assertSame('vfs://foo/bar/baz.txt', $link->target());

        $directoryTarget = fn () => $link->setTarget($this->directory('foo'));
        $this->assertExceptionType(Exception\UnexpectedNodeType::class, $directoryTarget);

        $filesystemMismatch = fn () => $link->setTarget(new Doubles\FakeLocalNode());
        $this->assertExceptionType(Exception\IOException\UnableToCreate::class, $filesystemMismatch);

        $indirectTarget = fn () => $link->setTarget($this->link('dir.lnk'));
        $this->assertExceptionType(Exception\IOException\UnableToCreate::class, $indirectTarget);
    }

    public function test_invalid_link(): void
    {
        $validate = fn () => $this->directory('inv.lnk')->validated();
        $this->assertExceptionType(Exception\UnexpectedNodeType::class, $validate, 'directory');

        $validate = fn () => $this->file('inv.lnk')->validated();
        $this->assertExceptionType(Exception\UnexpectedNodeType::class, $validate, 'file');
    }

    private function assertExists(VirtualNode $node): void
    {
        $this->assertTrue($node->exists());
    }

    private function assertNotExists(VirtualNode $node): void
    {
        $this->assertFalse($node->exists());
    }
}
