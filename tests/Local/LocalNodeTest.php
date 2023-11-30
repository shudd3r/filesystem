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

use Shudd3r\Filesystem\Tests\NodeTests;
use Shudd3r\Filesystem\Node;
use Shudd3r\Filesystem\Exception;


class LocalNodeTest extends NodeTests
{
    use LocalFilesystemSetup;

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

        $node = $root->node('foo');
        $this->assertSame($node, $node->validated(Node::READ | Node::WRITE | Node::REMOVE));

        $node = $root->node('bar');
        $this->assertSame($node, $node->validated(Node::READ));
        $this->assertExceptionType(Exception\FailedPermissionCheck::class, fn () => $node->validated(Node::WRITE));

        $node = $root->node('baz');
        $this->assertSame($node, $node->validated(Node::WRITE));
        $this->assertExceptionType(Exception\FailedPermissionCheck::class, fn () => $node->validated(Node::READ));
    }

    public function test_node_from_non_writable_directory_cannot_be_removed(): void
    {
        $root = $this->root(['foo' => ['bar' => '...']], ['foo' => Node::READ]);
        $node = $root->node('foo/bar');
        $this->assertExceptionType(Exception\FailedPermissionCheck::class, fn () => $node->remove());
    }

    public function test_root_node_cannot_be_removed(): void
    {
        $root = $this->root()->node();
        $this->assertFalse($root->isRemovable());
        $this->assertExceptionType(Exception\FailedPermissionCheck::class, fn () => $root->remove());
    }
}
