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

use PHPUnit\Framework\TestCase;
use Shudd3r\Filesystem\Local\LocalNode;
use Shudd3r\Filesystem\Local\Pathname;
use Shudd3r\Filesystem\Exception;
use Shudd3r\Filesystem\Node;
use Shudd3r\Filesystem\Tests\Doubles;
use Shudd3r\Filesystem\Tests\Fixtures;


class LocalNodeTest extends TestCase
{
    use Fixtures\TempFilesHandling;
    use Fixtures\ExceptionAssertion;

    public function test_root_node_name_is_empty(): void
    {
        $this->assertEmpty($this->node()->name());
    }

    public function test_node_name_returns_relative_pathname(): void
    {
        $normalizedName = self::$temp->normalized('foo/bar');
        $this->assertSame($normalizedName, $this->node('foo/bar')->name());
    }

    public function test_pathname_returns_absolute_node_path(): void
    {
        $rootPath = self::$temp->directory();
        $this->assertSame($rootPath, $this->node()->pathname());

        $nodePath = self::$temp->name('foo/bar');
        $this->assertSame($nodePath, $this->node('foo/bar', false)->pathname());
    }

    public function test_permissions_for_existing_node(): void
    {
        $path = self::$temp->directory('foo');
        $node = $this->node('foo');

        $this->assertTrue($node->isReadable());
        $this->assertTrue($node->isWritable());
        $this->assertTrue($node->isRemovable());

        self::override('is_readable', $path, false);
        $this->assertFalse($node->isReadable());
        $this->assertTrue($node->isWritable());
        $this->assertFalse($node->isRemovable());

        self::override('is_writable', $path, false);
        $this->assertFalse($node->isReadable());
        $this->assertFalse($node->isWritable());
        $this->assertFalse($node->isRemovable());
    }

    public function test_permissions_for_not_existing_node_depend_on_ancestor_permissions(): void
    {
        $path = self::$temp->directory('foo');
        $node = $this->node('foo/bar/dir', false);

        $this->assertTrue($node->isReadable());
        $this->assertTrue($node->isWritable());
        $this->assertTrue($node->isRemovable());

        self::override('is_readable', $path, false);
        $this->assertFalse($node->isReadable());
        $this->assertTrue($node->isWritable());
        $this->assertTrue($node->isRemovable());

        self::override('is_writable', $path, false);
        $this->assertFalse($node->isReadable());
        $this->assertFalse($node->isWritable());
        $this->assertFalse($node->isRemovable());
    }

    public function test_permissions_for_invalid_node_type_return_false(): void
    {
        self::$temp->file('foo/exists');
        $node = $this->node('foo/exists', false);

        $this->assertFalse($node->isReadable());
        $this->assertFalse($node->isWritable());
        $this->assertFalse($node->isRemovable());
    }

    public function test_permissions_for_unreachable_path_returns_false(): void
    {
        self::$temp->file('foo/file');
        $node = $this->node('foo/file/expanded', false);

        $this->assertFalse($node->isReadable());
        $this->assertFalse($node->isWritable());
        $this->assertFalse($node->isRemovable());
    }

    public function test_instance_validation_for_unreachable_paths_throws_exception(): void
    {
        $file = self::$temp->file('foo/bar.file');
        self::$temp->symlink($file, 'file.link');
        self::$temp->symlink('', 'foo/dead.link');

        $unreachablePaths = [
            'foo/bar.file'       => Exception\UnexpectedNodeType::class,
            'foo/bar.file/path'  => Exception\UnexpectedLeafNode::class,
            'file.link'          => Exception\UnexpectedNodeType::class,
            'file.link/path'     => Exception\UnexpectedLeafNode::class,
            'foo/dead.link'      => Exception\UnexpectedNodeType::class,
            'foo/dead.link/path' => Exception\UnexpectedLeafNode::class
        ];

        foreach ($unreachablePaths as $name => $expectedException) {
            $node = $this->node($name, false);
            $this->assertExceptionType($expectedException, fn () => $node->validated(), $name);
        }
    }

    public function test_instance_validation_with_access_permissions(): void
    {
        $node = $this->node('foo/bar.txt', false);
        $this->assertSame($node, $node->validated(Node::READ | Node::WRITE | Node::REMOVE));

        $directory = self::$temp->directory('foo');
        self::override('is_readable', $directory, false);
        $check = fn () => $node->validated(Node::READ);
        $this->assertExceptionType(Exception\FailedPermissionCheck::class, $check);
        $this->assertSame($node, $node->validated(Node::WRITE));

        self::override('is_writable', $directory, false);
        $check = fn () => $node->validated(Node::WRITE);
        $this->assertExceptionType(Exception\FailedPermissionCheck::class, $check);

        $file = self::$temp->file('foo/bar.txt');
        $node = $this->node('foo/bar.txt');
        $this->assertSame($node, $node->validated(Node::READ | Node::WRITE));

        self::override('is_writable', $file, false);
        $check = fn () => $node->validated(Node::WRITE | Node::READ);
        $this->assertExceptionType(Exception\FailedPermissionCheck::class, $check);
        $this->assertSame($node, $node->validated(Node::READ));

        self::override('is_readable', $file, false);
        self::override('is_writable', $file, true);
        $check = fn () => $node->validated(Node::WRITE | Node::READ);
        $this->assertExceptionType(Exception\FailedPermissionCheck::class, $check);
        $this->assertSame($node, $node->validated(Node::WRITE));
    }

    public function test_root_node_cannot_be_removed(): void
    {
        $root = $this->node();
        $this->assertFalse($root->isRemovable());
        $this->assertExceptionType(Exception\FailedPermissionCheck::class, fn () => $root->remove());
    }

    public function test_remove_for_not_existing_node_is_ignored(): void
    {
        $root = new Doubles\FakeLocalNode(Pathname::root(self::$temp->directory()), '', false);
        $this->assertFalse($root->isRemovable());
        $root->remove();
        $this->assertFalse($root->removed);
    }

    public function test_node_of_non_writable_directory_cannot_be_removed(): void
    {
        $path = self::$temp->directory('foo/bar');
        self::override('is_writable', dirname($path), false);
        $node   = $this->node('foo/bar');
        $remove = fn () => $node->remove();
        $this->assertExceptionType(Exception\FailedPermissionCheck::class, $remove);
    }

    private function node(string $name = '', bool $exists = true): LocalNode
    {
        return new Doubles\FakeLocalNode(Pathname::root(self::$temp->directory()), $name, $exists);
    }
}
