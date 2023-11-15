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

    public function test_permissions_for_existing_node(): void
    {
        $node = $this->root(['foo' => []])->node('foo');
        $this->assertTrue($node->isReadable());
        $this->assertTrue($node->isWritable());
        $this->assertTrue($node->isRemovable());

        $this->override('is_readable', false, $node->pathname());
        $this->assertFalse($node->isReadable());
        $this->assertTrue($node->isWritable());
        $this->assertFalse($node->isRemovable());

        $this->override('is_writable', false, $node->pathname());
        $this->assertFalse($node->isReadable());
        $this->assertFalse($node->isWritable());
        $this->assertFalse($node->isRemovable());
    }

    public function test_permissions_for_not_existing_node_depend_on_ancestor_permissions(): void
    {
        $node = $this->root(['foo' => []])->node('foo/bar/dir');
        $this->assertTrue($node->isReadable());
        $this->assertTrue($node->isWritable());
        $this->assertTrue($node->isRemovable());

        $this->override('is_readable', false, $this->path('foo'));
        $this->assertFalse($node->isReadable());
        $this->assertTrue($node->isWritable());
        $this->assertTrue($node->isRemovable());

        $this->override('is_writable', false, $this->path('foo'));
        $this->assertFalse($node->isReadable());
        $this->assertFalse($node->isWritable());
        $this->assertFalse($node->isRemovable());
    }

    public function test_permissions_for_invalid_node_type_return_false(): void
    {
        $node = $this->root(['foo' => ['exists' => '']])->node('foo/exists', false);
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
        $node = $this->root(['foo' => []])->node('foo/bar.txt');
        $this->assertSame($node, $node->validated(Node::READ | Node::WRITE | Node::REMOVE));

        $this->override('is_readable', false, $this->path('foo'));
        $check = fn () => $node->validated(Node::READ);
        $this->assertExceptionType(Exception\FailedPermissionCheck::class, $check);
        $this->assertSame($node, $node->validated(Node::WRITE));

        $this->override('is_writable', false, $this->path('foo'));
        $check = fn () => $node->validated(Node::WRITE);
        $this->assertExceptionType(Exception\FailedPermissionCheck::class, $check);

        $node = $this->root(['foo' => ['bar.txt' => '']])->node('foo/bar.txt');
        $this->assertSame($node, $node->validated(Node::READ | Node::WRITE));

        $this->override('is_writable', false, $this->path('foo/bar.txt'));
        $check = fn () => $node->validated(Node::WRITE | Node::READ);
        $this->assertExceptionType(Exception\FailedPermissionCheck::class, $check);
        $this->assertSame($node, $node->validated(Node::READ));

        $this->override('is_readable', false, $this->path('foo/bar.txt'));
        $this->override('is_writable', true, $this->path('foo/bar.txt'));
        $check = fn () => $node->validated(Node::WRITE | Node::READ);
        $this->assertExceptionType(Exception\FailedPermissionCheck::class, $check);
        $this->assertSame($node, $node->validated(Node::WRITE));
    }

    public function test_node_of_non_writable_directory_cannot_be_removed(): void
    {
        $node = $this->root(['foo' => ['bar' => []]])->node('foo/bar');
        $this->override('is_writable', false, $this->path('foo'));
        $remove = fn () => $node->remove();
        $this->assertExceptionType(Exception\FailedPermissionCheck::class, $remove);
    }

    public function test_root_node_cannot_be_removed(): void
    {
        $root = $this->root()->node();
        $this->assertFalse($root->isRemovable());
        $this->assertExceptionType(Exception\FailedPermissionCheck::class, fn () => $root->remove());
    }
}
