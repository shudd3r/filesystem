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

use Shudd3r\Filesystem\Node;
use Shudd3r\Filesystem\Exception;
use Shudd3r\Filesystem\Tests\Doubles;


class VirtualNodeTest extends VirtualFilesystemTests
{
    public function test_name_returns_relative_pathname(): void
    {
        $this->assertSame('foo/bar/baz', $this->node('foo/bar/baz')->name());
    }

    public function test_pathname_returns_absolute_filesystem_path(): void
    {
        $this->assertSame('vfs://foo/bar/baz', $this->node('foo/bar/baz')->pathname());
    }

    public function test_exists_for_existing_node_returns_true(): void
    {
        $this->assertTrue($this->node('foo', ['foo' => ''])->exists());
    }

    public function test_exists_for_not_existing_node_returns_false(): void
    {
        $this->assertFalse($this->node('foo', ['foo' => ''])->withTypeMismatch()->exists());
        $this->assertFalse($this->node('bar', ['foo' => ''])->exists());
    }

    public function test_node_permissions_for_valid_node_return_true(): void
    {
        $node = $this->node('foo.txt', ['foo.txt' => 'contents...']);
        $this->assertTrue($node->isReadable());
        $this->assertTrue($node->isWritable());
        $this->assertTrue($node->isRemovable());
    }

    public function test_permissions_for_invalid_node_return_false(): void
    {
        $node = $this->node('foo.txt/exists', ['foo.txt' => 'contents...']);
        $this->assertFalse($node->isReadable());
        $this->assertFalse($node->isWritable());
        $this->assertFalse($node->isRemovable());
    }

    public function test_validated_for_existing_node_returns_node_instance(): void
    {
        $node = $this->node('foo', ['foo' => '']);
        $this->assertSame($node, $node->validated());
    }

    public function test_validated_for_invalid_path_throws_exception(): void
    {
        $node = $this->node('file/bar', ['file' => '']);
        $this->assertExceptionType(Exception\UnexpectedLeafNode::class, fn () => $node->validated());

        $node = $this->node('foo/bar.txt', ['foo' => ['bar.txt' => '']])->withTypeMismatch();
        $this->assertExceptionType(Exception\UnexpectedNodeType::class, fn () => $node->validated());
    }

    public function test_validated_for_stale_link_throws_exception(): void
    {
        $node = $this->node('foo.lnk', ['foo.lnk' => '@bar.txt']);
        $this->assertExceptionType(Exception\UnexpectedNodeType::class, fn () => $node->validated());
    }

    public function test_validated_with_exists_flag_for_not_existing_path_throws_exception(): void
    {
        $node = $this->node('foo', ['bar' => '']);
        $this->assertExceptionType(Exception\NodeNotFound::class, fn () => $node->validated(Node::EXISTS));
    }

    public function test_remove_for_not_existing_node_is_ignored(): void
    {
        $root = $this->createNodes(['foo' => ['empty' => []]]);
        Doubles\FakeVirtualNode::fromRootDirectory($root, 'foo/empty')->withTypeMismatch()->remove();
        $this->assertEquals($this->createNodes(['foo' => ['empty' => []]]), $root);
    }

    public function test_remove_for_existing_node_deletes_node(): void
    {
        $root = $this->createNodes(['foo' => ['empty' => []]]);
        Doubles\FakeVirtualNode::fromRootDirectory($root, 'foo/empty')->remove();
        $this->assertEquals($this->createNodes(['foo' => []]), $root);
    }

    private function node(string $name, array $structure = null): Doubles\FakeVirtualNode
    {
        $root = $this->createNodes($structure ?? $this->exampleStructure());
        return Doubles\FakeVirtualNode::fromRootDirectory($root, $name);
    }
}
