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


class LinkedNodeTest extends TestCase
{
    public function test_isLink_method_returns_true(): void
    {
        $this->assertTrue($this->linked()->isLink());
    }

    public function test_target_method_returns_link_targetPath(): void
    {
        $this->assertSame('vfs://foo/bar', $this->linked()->target());
    }

    public function test_setTarget_method_changes_link_targetPath(): void
    {
        $linked = $this->linked($link);
        $linked->setTarget('vfs://new/path');
        $this->assertSame('vfs://new/path', $link->target());
    }

    public function test_other_methods_are_delegated_to_wrapped_node(): void
    {
        $node   = new TreeNode\Directory(['foo' => new TreeNode\File()]);
        $linked = $this->linked($lnk, $node);
        $this->assertTrue($linked->exists());
        $this->assertTrue($linked->isDir());
        $this->assertTrue($linked->isValid());
        $this->assertSame(['foo'], iterator_to_array($linked->filenames(), false));

        $node   = new TreeNode\File('old contents...');
        $linked = $this->linked($lnk, $node);
        $this->assertTrue($linked->isFile());
        $this->assertSame('old contents...', $linked->contents());
        $linked->putContents('new contents...');
        $this->assertSame('new contents...', $linked->contents());

        $node   = new TreeNode\InvalidNode('foo', 'bar');
        $linked = $this->linked($lnk, $node);
        $this->assertFalse($linked->exists());
        $this->assertFalse($linked->isFile());
        $this->assertFalse($linked->isDir());
        $this->assertFalse($linked->isValid());
        $this->assertSame(['foo', 'bar'], $linked->missingSegments());
    }

    private function linked(?TreeNode\Link &$link = null, ?TreeNode &$node = null): TreeNode\LinkedNode
    {
        $link ??= new TreeNode\Link('vfs://foo/bar');
        $node ??= new TreeNode\Directory();
        return new TreeNode\LinkedNode($link, $node);
    }
}
