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

use PHPUnit\Framework\TestCase;
use Shudd3r\Filesystem\Virtual\Root;
use Shudd3r\Filesystem\Virtual\TreeNode\Directory;
use Shudd3r\Filesystem\Virtual\TreeNode\MissingNode;
use Shudd3r\Filesystem\Exception\UnsupportedOperation;


class RootTest extends TestCase
{
    public function test_node_method(): void
    {
        $root = new Root('vfs://root', $rootDir = new Directory([
            'foo' => $foo = new Directory([
                'bar' => $bar = new Directory()
            ])
        ]));

        $this->assertSame($rootDir, $root->node('vfs://root'));
        $this->assertSame($foo, $root->node('vfs://root/foo'));
        $this->assertSame($bar, $root->node('vfs://root/foo/bar'));
        $this->assertSame($bar, $root->node('vfs://root/foo')->node('bar'));
        $this->assertEquals(new MissingNode('baz'), $root->node('vfs://root/foo/bar/baz'));
    }

    public function test_node_for_not_matching_root_path_throws_exception(): void
    {
        $root = new Root('vfs://');
        $this->expectException(UnsupportedOperation::class);
        $root->node('virtual://root/path');
    }
}
