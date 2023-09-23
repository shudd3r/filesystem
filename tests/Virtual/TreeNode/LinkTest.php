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
use Shudd3r\Filesystem\Virtual\TreeNode\Link;


class LinkTest extends TestCase
{
    public function test_isLink_method_returns_true(): void
    {
        $this->assertTrue($this->link('foo')->isLink());
    }

    public function test_target_method_returns_instance_targetPath(): void
    {
        $this->assertSame('foo/bar', $this->link('foo/bar')->target());
    }

    public function test_setTarget_method_changes_link_target(): void
    {
        $link = $this->link('foo/bar');
        $link->setTarget('bar/baz');
        $this->assertSame('bar/baz', $link->target());
    }

    public function test_node_method_returns_link_with_missing_path(): void
    {
        $link = $this->link('baz')->node('foo/bar');
        $this->assertInstanceOf(Link::class, $link);
        $this->assertSame('foo/bar', $link->missingPath());
    }

    private function link(string $targetPath): Link
    {
        return new Link($targetPath);
    }
}
