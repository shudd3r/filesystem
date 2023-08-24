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

use PHPUnit\Framework\TestCase;
use Shudd3r\Filesystem\Virtual\NodeTree;
use Shudd3r\Filesystem\Virtual\VirtualDirectory;
use Shudd3r\Filesystem\Virtual\VirtualFile;
use Shudd3r\Filesystem\Virtual\VirtualLink;
use Shudd3r\Filesystem\Virtual\VirtualNode;
use Shudd3r\Filesystem\FilesystemException;
use Shudd3r\Filesystem\Exception;


class VirtualFilesystemTest extends TestCase
{
    private const EXAMPLE_STRUCTURE = [
        'foo' => [
            'bar'      => ['baz.txt' => 'baz contents'],
            'file.lnk' => ['link' => true, 'target' => 'virtual://bar.txt']
        ],
        'bar.txt' => 'bar contents',
        'dir.lnk' => ['link' => true, 'target' => 'virtual://foo/bar'],
        'inv.lnk' => ['link' => true, 'target' => 'virtual://foo/baz']
    ];

    private static NodeTree $tree;

    protected function setUp(): void
    {
        self::$tree = new NodeTree(self::EXAMPLE_STRUCTURE);
    }

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
        $tree = new NodeTree(self::EXAMPLE_STRUCTURE);
        $this->file('foo/bar/baz/file.txt')->remove();
        $this->assertEquals(self::$tree, $tree);

        $this->directory('foo/bar/baz.txt')->remove();
        $this->assertEquals(self::$tree, $tree);
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
        $this->assertNotExists($this->link('inv.lnk'));
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
        $directory = $this->directory('foo');
        $this->assertSame('virtual://foo', $directory->pathname());
        $this->assertSame('foo', $directory->name());
        $subdirectory = $directory->subdirectory('bar/baz');
        $this->assertSame('foo/bar/baz', $subdirectory->name());

        $directory = $directory->asRoot();
        $this->assertSame('', $directory->name());
        $subdirectory = $directory->subdirectory('bar/baz');
        $this->assertSame('bar/baz', $subdirectory->name());
        $this->assertSame('virtual://foo/bar/baz', $subdirectory->pathname());
        $this->assertSame($subdirectory, $subdirectory->validated());
        $this->assertExists($directory->file('bar/baz.txt'));
        $this->assertExists($directory->link('file.lnk'));

        $notExistingRoot = fn () => $subdirectory->asRoot();
        $this->assertExceptionType(Exception\RootDirectoryNotFound::class, $notExistingRoot);

        $validateExisting = fn () => $subdirectory->validated(VirtualNode::EXISTS);
        $this->assertExceptionType(Exception\NodeNotFound::class, $validateExisting);

        $validatePath = fn () => $directory->subdirectory('bar/baz.txt/dir')->validated();
        $this->assertExceptionType(Exception\UnexpectedLeafNode::class, $validatePath);

        $validatePath = fn () => $directory->subdirectory('bar/baz.txt')->validated();
        $this->assertExceptionType(Exception\UnexpectedNodeType::class, $validatePath);
    }

    public function test_file_contents(): void
    {
        $this->assertSame('baz contents', $this->file('foo/bar/baz.txt')->contents());
        $this->assertSame('bar contents', $this->file('foo/file.lnk')->contents());
    }

    public function test_link_target(): void
    {
        $link = $this->link('foo/file.lnk');
        $this->assertSame('virtual://bar.txt', $link->target());
        $this->file('bar.txt')->remove();
        $this->assertNull($link->target());
        $this->assertSame('virtual://bar.txt', $link->target(true));
    }

    public function test_invalid_link(): void
    {
        $validate = fn () => $this->directory('inv.lnk')->validated();
        $this->assertExceptionType(Exception\UnexpectedNodeType::class, $validate, 'directory');

        $validate = fn () => $this->directory('inv.lnk/subdirectory')->validated();
        $this->assertExceptionType(Exception\UnexpectedLeafNode::class, $validate, 'subdirectory');

        $validate = fn () => $this->file('inv.lnk')->validated();
        $this->assertExceptionType(Exception\UnexpectedNodeType::class, $validate, 'file');

        $validate = fn () => $this->file('inv.lnk/foo/file.txt')->validated();
        $this->assertExceptionType(Exception\UnexpectedLeafNode::class, $validate, 'subdirectory file');
    }

    private function assertExists(VirtualNode $node): void
    {
        $this->assertTrue($node->exists());
    }

    private function assertNotExists(VirtualNode $node): void
    {
        $this->assertFalse($node->exists());
    }

    private function assertExceptionType(string $expected, callable $procedure, string $case = ''): void
    {
        $title = $case ? 'Case "' . $case . '": ' : '';
        try {
            $procedure();
        } catch (FilesystemException $ex) {
            $message = $title . 'Unexpected Exception type - expected `%s` caught `%s`';
            $this->assertInstanceOf($expected, $ex, sprintf($message, $expected, get_class($ex)));
            return;
        }

        $this->fail(sprintf($title . 'No Exception thrown - expected `%s`', $expected));
    }

    private function directory(string $name = ''): VirtualDirectory
    {
        return new VirtualDirectory(self::$tree, 'virtual:/', $name);
    }

    private function file(string $name): VirtualFile
    {
        return new VirtualFile(self::$tree, 'virtual:/', $name);
    }

    private function link(string $name): VirtualLink
    {
        return new VirtualLink(self::$tree, 'virtual:/', $name);
    }
}
