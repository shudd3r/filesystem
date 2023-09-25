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


class DirectoryTest extends TestCase
{
    public function test_node_method(): void
    {
        $directory = new Directory([
            'foo' => $foo = new Directory([
                'bar' => $bar = new Directory()
            ])
        ]);

        $this->assertSame($directory, $directory->node());
        $this->assertSame($foo, $directory->node('foo'));
        $this->assertSame($bar, $directory->node('foo', 'bar'));
        $this->assertSame($bar, $directory->node('foo')->node('bar'));
        $this->assertEquals(new MissingNode('baz'), $directory->node('foo', 'bar', 'baz'));
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
        $this->assertInstanceOf(MissingNode::class, $directory->node('foo'));
        $directory->add('foo', $foo = new Directory());
        $this->assertSame($foo, $directory->node('foo'));
        $directory->add('foo', $newFoo = new Directory());
        $this->assertSame($newFoo, $directory->node('foo'));
    }

    public function test_remove_node(): void
    {
        $directory = new Directory([
            'foo' => new Directory(),
            'bar' => new Directory()
        ]);

        $directory->unlink('foo');
        $this->assertEquals(new Directory(['bar' => new Directory()]), $directory);
    }
}
