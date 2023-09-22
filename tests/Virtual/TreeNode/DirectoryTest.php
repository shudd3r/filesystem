<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Tests\Virtual\TreeNode;

use PHPUnit\Framework\TestCase;
use Shudd3r\Filesystem\Virtual\TreeNode\Directory;
use Shudd3r\Filesystem\Virtual\TreeNode\MissingNode;
use Shudd3r\Filesystem\Exception\UnsupportedOperation;


class DirectoryTest extends TestCase
{
    public function test_node_method(): void
    {
        $directory = new Directory([
            'foo' => $foo = new Directory([
                'bar' => $bar = new Directory()
            ])
        ]);

        $this->assertSame($directory, $directory->node(''));
        $this->assertSame($foo, $directory->node('foo'));
        $this->assertSame($bar, $directory->node('foo/bar'));
        $this->assertSame($bar, $directory->node('foo')->node('bar'));
        $this->assertInstanceOf(MissingNode::class, $directory->node('foo/bar/baz'));
    }

    public function test_isDir_returns_true(): void
    {
        $directory = new Directory();
        $this->assertTrue($directory->isDir());
    }

    public function test_files_returns_empty_iterator(): void
    {
        $directory = new Directory();
        $this->assertSame([], iterator_to_array($directory->filenames()));
    }

    public function test_adding_nodes(): void
    {
        $directory = new Directory();
        $this->assertInstanceOf(MissingNode::class, $directory->node('foo/bar'));
        $directory->add('foo/bar', $bar = new Directory());
        $this->assertSame($bar, $directory->node('foo/bar'));
    }

    public function test_adding_node_with_empty_path_throws_exception(): void
    {
        $directory = new Directory();
        $this->expectException(UnsupportedOperation::class);
        $directory->add('', new Directory());
    }

    public function test_adding_node_to_existing_path_throws_exception(): void
    {
        $directory = new Directory([
            'foo' => new Directory()
        ]);
        $this->expectException(UnsupportedOperation::class);
        $directory->add('foo/bar', new Directory());
    }

    public function test_remove_nodes(): void
    {
        $directory = new Directory([
            'foo' => $foo = new Directory([
                'bar' => $bar = new Directory()
            ])
        ]);

        $directory->remove('foo/bar/baz');
        $this->assertSame($bar, $directory->node('foo/bar'));
        $directory->remove('foo/bar');
        $this->assertInstanceOf(MissingNode::class, $directory->node('foo/bar'));
        $this->assertSame($foo, $directory->node('foo'));
    }

    public function test_remove_with_empty_path_throws_exception(): void
    {
        $directory = new Directory();
        $this->expectException(UnsupportedOperation::class);
        $directory->remove('');
    }
}
