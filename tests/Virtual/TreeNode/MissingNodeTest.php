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
use Shudd3r\Filesystem\Virtual\TreeNode\MissingNode;


class MissingNodeTest extends TestCase
{
    public function test_exists_method_returns_false(): void
    {
        $this->assertFalse($this->missingNode('foo')->exists());
    }

    public function test_missingSegments_method_returns_instance_segments(): void
    {
        $this->assertSame(['foo', 'bar'], $this->missingNode('foo', 'bar')->missingSegments());
    }

    public function test_node_method_returns_new_instance_with_expanded_missing_path_segments(): void
    {
        $missingNode = $this->missingNode('foo');
        $this->assertNotSame($missingNode, $missingNode->node('bar', 'baz'));
        $this->assertEquals($this->missingNode('foo', 'bar', 'baz'), $missingNode->node('bar', 'baz'));
    }

    private function missingNode(string ...$missingSegments): MissingNode
    {
        return new MissingNode(...$missingSegments);
    }
}
