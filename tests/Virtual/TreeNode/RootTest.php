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
use Shudd3r\Filesystem\Virtual\TreeNode\Root;
use Shudd3r\Filesystem\Virtual\TreeNode\Directory;
use Shudd3r\Filesystem\Virtual\TreeNode\MissingNode;
use Shudd3r\Filesystem\Exception\UnsupportedOperation;
use Exception;


class RootTest extends TestCase
{
    public function test_node_method(): void
    {
        $root = new Root('vfs://root', [
            'foo' => $foo = new Directory([
                'bar' => $bar = new Directory()
            ])
        ]);

        $this->assertException(fn () => $root->node('not/root'));
        $this->assertSame($root, $root->node('vfs://root'));
        $this->assertSame($foo, $root->node('vfs://root/foo'));
        $this->assertSame($bar, $root->node('vfs://root/foo/bar'));
        $this->assertSame($bar, $root->node('vfs://root/foo')->node('bar'));
        $this->assertInstanceOf(MissingNode::class, $root->node('vfs://root/foo/bar/baz'));
    }

    public function test_adding_nodes(): void
    {
        $root = new Root('vfs://');
        $this->assertInstanceOf(MissingNode::class, $root->node('vfs://foo/bar'));
        $root->add('vfs://foo/bar', $bar = new Directory());
        $this->assertSame($bar, $root->node('vfs://foo/bar'));
        $this->assertException(fn () => $root->add('vfs://', new Directory()));
    }

    public function test_remove_nodes(): void
    {
        $root = new Root('vfs://root', [
            'foo' => $foo = new Directory([
                'bar' => $bar = new Directory()
            ])
        ]);

        $root->remove('vfs://root/foo/bar/baz');
        $this->assertSame($bar, $root->node('vfs://root/foo/bar'));
        $root->remove('vfs://root/foo/bar');
        $this->assertInstanceOf(MissingNode::class, $root->node('vfs://root/foo/bar'));
        $this->assertSame($foo, $root->node('vfs://root/foo'));
        $this->assertException(fn () => $root->remove('vrs://root/'));
    }

    private function assertException(callable $methodCall): void
    {
        $expected = UnsupportedOperation::class;
        try {
            $methodCall();
        } catch (Exception $ex) {
            $message = 'Unexpected Exception type - expected `%s` caught `%s`';
            $this->assertInstanceOf($expected, $ex, sprintf($message, $expected, get_class($ex)));
            return;
        }

        $this->fail(sprintf('No Exception thrown - expected `%s`', $expected));
    }
}
