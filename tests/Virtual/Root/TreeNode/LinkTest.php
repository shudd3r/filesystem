<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Tests\Virtual\Root\TreeNode;

use PHPUnit\Framework\TestCase;
use Shudd3r\Filesystem\Virtual\Root\TreeNode\Link;


class LinkTest extends TestCase
{
    public function test_isLink_method_returns_true(): void
    {
        $this->assertTrue($this->link('foo')->isLink());
    }

    public function test_target_method_returns_instance_targetPath(): void
    {
        $this->assertSame('vfs://foo/bar', $this->link('vfs://foo/bar')->target());
    }

    public function test_setTarget_method_changes_link_target(): void
    {
        $link = $this->link('vfs://foo/bar');
        $link->setTarget('vfs://bar/baz');
        $this->assertSame('vfs://bar/baz', $link->target());
    }

    public function test_node_method_returns_new_instance_with_expanded_missing_path_segments(): void
    {
        $link = $this->link('baz');
        $this->assertInstanceOf(Link::class, $expanded = $link->node('foo', 'bar'));
        $this->assertNotSame($link, $expanded);
        $this->assertSame(['foo', 'bar'], $expanded->missingSegments());
        $this->assertSame(['foo', 'bar', 'baz'], $expanded->node('baz')->missingSegments());
    }

    private function link(string $targetPath): Link
    {
        return new Link($targetPath);
    }
}
