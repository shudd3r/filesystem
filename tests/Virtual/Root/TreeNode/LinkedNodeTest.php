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
use Shudd3r\Filesystem\Virtual\Root\TreeNode;
use Shudd3r\Filesystem\Node;


class LinkedNodeTest extends TestCase
{
    public function test_isLink_method_returns_true(): void
    {
        $this->assertTrue($this->node()->isLink());
    }

    public function test_target_method_returns_link_targetPath(): void
    {
        $this->assertSame('vfs://foo/bar', $this->node()->target());
    }

    public function test_setTarget_method_changes_link_targetPath(): void
    {
        $node = $this->node($link);
        $node->setTarget('vfs://new/path');
        $this->assertSame('vfs://new/path', $link->target());
    }

    public function test_other_methods_are_delegated_to_wrapped_node(): void
    {
        $linked = new TreeNode\Directory(['foo' => new TreeNode\File()]);
        $node   = $this->node($lnk, $linked);
        $this->assertTrue($node->exists());
        $this->assertTrue($node->isDir());
        $this->assertSame(['foo'], iterator_to_array($node->filenames(), false));

        $linked = new TreeNode\File('old contents...');
        $node   = $this->node($lnk, $linked);
        $this->assertTrue($node->isFile());
        $this->assertSame('old contents...', $node->contents());
        $node->putContents('new contents...');
        $this->assertSame('new contents...', $node->contents());

        $linked = new TreeNode\InvalidNode('foo', 'bar');
        $node   = $this->node($lnk, $linked);
        $this->assertFalse($node->exists());
        $this->assertFalse($node->isFile());
        $this->assertFalse($node->isDir());
        $this->assertSame(['foo', 'bar'], $node->missingSegments());
    }

    public function test_isAllowed_returns_permissions_of_wrapped_node(): void
    {
        $linked = new TreeNode\File('foo', Node::WRITE);
        $node   = $this->node($lnk, $linked);
        $this->assertFalse($node->isAllowed(Node::READ | Node::WRITE));
        $this->assertTrue($node->isAllowed(Node::WRITE));
        $this->assertFalse($node->isAllowed(Node::READ));
    }

    private function node(?TreeNode\Link &$link = null, ?TreeNode &$linked = null): TreeNode\LinkedNode
    {
        $link ??= new TreeNode\Link('vfs://foo/bar');
        $linked ??= new TreeNode\Directory();
        return new TreeNode\LinkedNode($link, $linked);
    }
}
