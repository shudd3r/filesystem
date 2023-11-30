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

use Shudd3r\Filesystem\Tests\NodeTests;
use Shudd3r\Filesystem\Node;
use Shudd3r\Filesystem\Exception;


class VirtualNodeTest extends NodeTests
{
    use VirtualFilesystemSetup;

    public function test_permissions_for_existing_node(): void
    {
        $root = $this->root(
            ['foo' => [], 'bar' => ['file' => '...'], 'baz' => []],
            ['bar' => Node::READ, 'baz' => Node::WRITE]
        );

        $node = $root->node('foo');
        $this->assertTrue($node->isReadable());
        $this->assertTrue($node->isWritable());
        $this->assertTrue($node->isRemovable());

        $node = $root->node('bar');
        $this->assertTrue($node->isReadable());
        $this->assertFalse($node->isWritable());
        $this->assertTrue($node->isRemovable());

        $node = $root->node('bar/file');
        $this->assertFalse($node->isReadable());
        $this->assertFalse($node->isWritable());
        $this->assertFalse($node->isRemovable());

        $node = $root->node('baz');
        $this->assertFalse($node->isReadable());
        $this->assertTrue($node->isWritable());
        $this->assertTrue($node->isRemovable());
    }

    public function test_permissions_for_not_existing_node_depend_on_ancestor_permissions(): void
    {
        $root = $this->root(
            ['foo' => [], 'bar' => [], 'baz' => []],
            ['bar' => Node::READ, 'baz' => Node::WRITE]
        );

        $node = $root->node('foo/file');
        $this->assertTrue($node->isReadable());
        $this->assertTrue($node->isWritable());
        $this->assertTrue($node->isRemovable());

        $node = $root->node('bar/file');
        $this->assertFalse($node->isReadable());
        $this->assertFalse($node->isWritable());
        $this->assertFalse($node->isRemovable());

        $node = $root->node('baz/file');
        $this->assertFalse($node->isReadable());
        $this->assertTrue($node->isWritable());
        $this->assertTrue($node->isRemovable());
    }

    public function test_permissions_for_invalid_node_type_return_false(): void
    {
        $node = $this->root(['foo' => ['exists' => '']])->node('foo/exists', false);
        $this->assertTrue($node->isReadable());
        $this->assertTrue($node->isWritable());
        $this->assertTrue($node->isRemovable());
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
}
