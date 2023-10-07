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

use Shudd3r\Filesystem\Local\LocalNode;
use Shudd3r\Filesystem\Exception;
use Shudd3r\Filesystem\Node;
use Shudd3r\Filesystem\Tests\Doubles;


class LocalNodeTest extends LocalFilesystemTests
{
    public function test_instantiation(): void
    {
        $this->assertInstanceOf(LocalNode::class, $this->node('foo/bar'));
    }

    public function test_root_node_name_is_empty(): void
    {
        $this->assertEmpty($this->node()->name());
    }

    public function test_pathname_returns_absolute_node_path(): void
    {
        $this->assertSame($this->path(), $this->node()->pathname());
        $this->assertSame($this->path('foo/bar'), $this->node('foo/bar', false)->pathname());
    }

    public function test_permissions_for_existing_node(): void
    {
        $node = $this->node('foo', true, ['foo' => []]);
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
        $node = $this->node('foo/bar/dir', false, ['foo' => []]);
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
        $node = $this->node('foo/exists', false, ['foo' => ['exists' => '']]);
        $this->assertFalse($node->isReadable());
        $this->assertFalse($node->isWritable());
        $this->assertFalse($node->isRemovable());
    }

    public function test_permissions_for_unreachable_path_returns_false(): void
    {
        $node = $this->node('foo/file/expanded', false, ['foo' => ['file' => '']]);
        $this->assertFalse($node->isReadable());
        $this->assertFalse($node->isWritable());
        $this->assertFalse($node->isRemovable());
    }

    public function test_instance_validation_for_unreachable_paths_throws_exception(): void
    {
        $this->root(['foo' => ['bar.file' => '', 'dead.lnk' => 'not/exists'], 'file.lnk' => 'foo/bar.file']);
        $unreachablePaths = [
            'foo/bar.file'      => Exception\UnexpectedNodeType::class,
            'foo/bar.file/path' => Exception\UnexpectedLeafNode::class,
            'file.lnk'          => Exception\UnexpectedNodeType::class,
            'file.lnk/path'     => Exception\UnexpectedLeafNode::class,
            'foo/dead.lnk'      => Exception\UnexpectedNodeType::class,
            'foo/dead.lnk/path' => Exception\UnexpectedLeafNode::class
        ];

        foreach ($unreachablePaths as $name => $expectedException) {
            $node = $this->node($name, false);
            $this->assertExceptionType($expectedException, fn () => $node->validated(), $name);
        }
    }

    public function test_instance_validation_with_access_permissions(): void
    {
        $node = $this->node('foo/bar.txt', false, ['foo' => []]);
        $this->assertSame($node, $node->validated(Node::READ | Node::WRITE | Node::REMOVE));

        $this->override('is_readable', false, $this->path('foo'));
        $check = fn () => $node->validated(Node::READ);
        $this->assertExceptionType(Exception\FailedPermissionCheck::class, $check);
        $this->assertSame($node, $node->validated(Node::WRITE));

        $this->override('is_writable', false, $this->path('foo'));
        $check = fn () => $node->validated(Node::WRITE);
        $this->assertExceptionType(Exception\FailedPermissionCheck::class, $check);

        $node = $this->node('foo/bar.txt', true, ['foo' => ['bar.txt' => '']]);
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

    public function test_validation_for_not_existing_instance_with_exist_assertion_throws_exception(): void
    {
        $node = $this->node('foo/bar.txt', false);
        $this->assertExceptionType(Exception\NodeNotFound::class, fn () => $node->validated(Node::EXISTS));
    }

    public function test_root_node_cannot_be_removed(): void
    {
        $root = $this->node();
        $this->assertFalse($root->isRemovable());
        $this->assertExceptionType(Exception\FailedPermissionCheck::class, fn () => $root->remove());
    }

    public function test_remove_for_not_existing_node_is_ignored(): void
    {
        $root = $this->node('', false);
        $this->assertFalse($root->isRemovable());
        $root->remove();
        $this->assertFalse($root->removed);
    }

    public function test_node_of_non_writable_directory_cannot_be_removed(): void
    {
        $node = $this->node('foo/bar', true, ['foo' => ['bar' => []]]);
        $this->override('is_writable', false, $this->path('foo'));
        $remove = fn () => $node->remove();
        $this->assertExceptionType(Exception\FailedPermissionCheck::class, $remove);
    }

    private function node(string $name = '', bool $exists = true, array $structure = []): Doubles\FakeLocalNode
    {
        if ($structure) { $this->root($structure); }
        return new Doubles\FakeLocalNode($this->path(), $name, $exists);
    }
}
