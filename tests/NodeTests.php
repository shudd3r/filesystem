<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Tests;

use Shudd3r\Filesystem\Node;
use Shudd3r\Filesystem\Exception;


abstract class NodeTests extends FilesystemTests
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
        $root = $this->root([]);
        $this->assertSame($this->path(), $root->node()->pathname());
        $this->assertSame($this->path('foo/bar'), $root->node('foo/bar', false)->pathname());
    }

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
        $this->assertFalse($node->isRemovable());

        $node = $root->node('bar/file');
        $this->assertTrue($node->isReadable());
        $this->assertTrue($node->isWritable());
        $this->assertFalse($node->isRemovable());

        $node = $root->node('baz');
        $this->assertFalse($node->isReadable());
        $this->assertTrue($node->isWritable());
        $this->assertFalse($node->isRemovable());
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
        $this->assertTrue($node->isReadable());
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
        $this->assertFalse($node->isReadable());
        $this->assertFalse($node->isWritable());
        $this->assertFalse($node->isRemovable());
    }

    public function test_permissions_for_unreachable_path_return_false(): void
    {
        $node = $this->root(['foo' => ['file' => '']])->node('foo/file/expanded');
        $this->assertFalse($node->isReadable());
        $this->assertFalse($node->isWritable());
        $this->assertFalse($node->isRemovable());
    }

    public function test_instance_validation_for_unreachable_paths_throws_exception(): void
    {
        $nodes = $this->root(['foo' => ['bar.txt' => '', 'dead.lnk' => '@not/exists'], 'file.lnk' => '@foo/bar.txt']);
        $unreachablePaths = [
            'foo/bar.txt'       => Exception\UnexpectedNodeType::class,
            'foo/bar.txt/path'  => Exception\UnexpectedLeafNode::class,
            'file.lnk'          => Exception\UnexpectedNodeType::class,
            'file.lnk/path'     => Exception\UnexpectedLeafNode::class,
            'foo/dead.lnk'      => Exception\UnexpectedNodeType::class,
            'foo/dead.lnk/path' => Exception\UnexpectedLeafNode::class
        ];

        foreach ($unreachablePaths as $name => $expectedException) {
            $node = $nodes->node($name, false);
            $this->assertExceptionType($expectedException, fn () => $node->validated(), $name);
        }
    }

    public function test_instance_validation_with_access_permissions(): void
    {
        $root = $this->root(
            ['foo' => [], 'bar' => [], 'baz' => []],
            ['bar' => Node::READ, 'baz' => Node::WRITE]
        );

        $validate = fn (Node $node, int $flags) => fn () => $node->validated($flags);

        $node = $root->node('foo');
        $this->assertSame($node, $node->validated(Node::READ | Node::WRITE | Node::REMOVE));

        $node = $root->node('bar');
        $this->assertSame($node, $node->validated(Node::READ));
        $this->assertExceptionType(Exception\PermissionDenied::class, $validate($node, Node::WRITE));
        $this->assertExceptionType(Exception\PermissionDenied::class, $validate($node, Node::READ | Node::REMOVE));

        $node = $root->node('baz');
        $this->assertSame($node, $node->validated(Node::WRITE));
        $this->assertExceptionType(Exception\PermissionDenied::class, $validate($node, Node::READ));
        $this->assertExceptionType(Exception\PermissionDenied::class, $validate($node, Node::WRITE | Node::REMOVE));
    }

    public function test_validation_for_not_existing_instance_with_exist_assertion_throws_exception(): void
    {
        $node = $this->root(['foo' => []])->node('foo/bar.txt');
        $this->assertExceptionType(Exception\NodeNotFound::class, fn () => $node->validated(Node::EXISTS));
    }

    public function test_remove_for_not_existing_node_is_ignored(): void
    {
        $root = $this->root(['foo' => ['bar' => []]]);
        $node = $root->node('foo/bar', false);
        $node->remove();
        $this->assertTrue($root->node('foo/bar')->exists());
    }

    public function test_remove_for_existing_node_deletes_node(): void
    {
        $node = $this->root(['foo' => ['bar' => []]])->node('foo/bar');
        $node->remove();
        $this->assertFalse($node->exists());
    }

    public function test_node_from_non_writable_directory_cannot_be_removed(): void
    {
        $root = $this->root(['foo' => ['bar' => '...', 'baz.lnk' => '@baz'], 'baz' => []], ['foo' => Node::READ]);
        $this->assertExceptionType(Exception\PermissionDenied::class, fn () => $root->node('foo/bar')->remove());
        $this->assertExceptionType(Exception\PermissionDenied::class, fn () => $root->node('foo/baz.lnk')->remove());
    }

    public function test_root_node_cannot_be_removed(): void
    {
        $root = $this->root()->node();
        $this->assertFalse($root->isRemovable());
        $this->assertExceptionType(Exception\PermissionDenied::class, fn () => $root->remove());
    }
}
