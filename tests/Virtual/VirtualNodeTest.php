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


class VirtualNodeTest extends VirtualFilesystemTests
{
    public function test_root_node_name_is_empty(): void
    {
        $this->assertEmpty($this->root([])->node()->name());
    }

    public function test_name_returns_relative_pathname(): void
    {
        $this->assertSame('foo/bar/baz', $this->root([])->node('foo/bar/baz')->name());
    }

    public function test_pathname_returns_absolute_filesystem_path(): void
    {
        $this->assertSame($this->path('foo/bar/baz'), $this->root([])->node('foo/bar/baz')->pathname());
    }

    public function test_exists_for_existing_node_returns_true(): void
    {
        $this->assertTrue($this->root(['foo' => ''])->node('foo')->exists());
    }

    public function test_exists_for_not_existing_node_returns_false(): void
    {
        $root = $this->root(['foo' => []]);
        $this->assertFalse($root->node('bar')->exists());
        $this->assertFalse($root->node('foo', false)->exists());
    }

    public function test_node_permissions_for_valid_node_return_true(): void
    {
        $node = $this->root(['foo.txt' => 'contents...'])->node('foo.txt');
        $this->assertTrue($node->isReadable());
        $this->assertTrue($node->isWritable());
        $this->assertTrue($node->isRemovable());
    }

    public function test_permissions_for_invalid_node_return_false(): void
    {
        $node = $this->root(['foo.txt' => 'contents...'])->node('foo.txt/exists');
        $this->assertFalse($node->isReadable());
        $this->assertFalse($node->isWritable());
        $this->assertFalse($node->isRemovable());
    }

    public function test_validated_for_existing_node_returns_node_instance(): void
    {
        $node = $this->root(['foo' => ''])->node('foo');
        $this->assertSame($node, $node->validated());
    }

    public function test_validated_for_invalid_path_throws_exception(): void
    {
        $node = $this->root(['file' => ''])->node('file/bar');
        $this->assertExceptionType(Exception\UnexpectedLeafNode::class, fn () => $node->validated());

        $node = $this->root(['foo' => ['bar.txt' => '']])->node('foo/bar.txt', false);
        $this->assertExceptionType(Exception\UnexpectedNodeType::class, fn () => $node->validated());
    }

    public function test_validated_for_stale_link_throws_exception(): void
    {
        $node = $this->root(['foo.lnk' => '@bar.txt'])->node('foo.lnk');
        $this->assertExceptionType(Exception\UnexpectedNodeType::class, fn () => $node->validated());
    }

    public function test_validated_with_exists_flag_for_not_existing_path_throws_exception(): void
    {
        $node = $this->root(['bar' => ''])->node('foo');
        $this->assertExceptionType(Exception\NodeNotFound::class, fn () => $node->validated(Node::EXISTS));
    }

    public function test_remove_for_not_existing_node_is_ignored(): void
    {
        $node = $this->root(['foo' => ['bar' => []]])->node('foo/bar', false);
        $node->remove();
        $this->assertFalse($node->exists());
    }

    public function test_remove_for_existing_node_deletes_node(): void
    {
        $node = $this->root(['foo' => ['empty' => []]])->node('foo/empty');
        $node->remove();
        $this->assertFalse($node->exists());
    }
}
