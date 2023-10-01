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
use Shudd3r\Filesystem\Virtual\Root\TreeNode\MissingNode;
use Shudd3r\Filesystem\Virtual\Root\TreeNode\InvalidNode;
use Shudd3r\Filesystem\Virtual\Root\TreeNode\ParentContext;
use Shudd3r\Filesystem\Virtual\Root\TreeNode\LinkedNode;
use Shudd3r\Filesystem\Virtual\Root\TreeNode\Directory;
use Shudd3r\Filesystem\Virtual\Root\TreeNode\File;
use Shudd3r\Filesystem\Virtual\Root\TreeNode\Link;
use Shudd3r\Filesystem\Exception;


class RootTest extends TestCase
{
    public function test_node_for_not_matching_root_path_throws_exception(): void
    {
        $root = $this->root();
        $this->expectException(Exception\UnsupportedOperation::class);
        $root->node('virtual://root/path');
    }

    public function test_node_for_not_existing_path_returns_MissingNode(): void
    {
        $root = $this->root();
        $this->assertInstanceOf(MissingNode::class, $node = $root->node('vfs://baz'));
        $this->assertSame(['baz'], $node->missingSegments());
        $this->assertInstanceOf(MissingNode::class, $node = $root->node('vfs://foo/empty/bar/baz'));
        $this->assertSame(['bar', 'baz'], $node->missingSegments());
        $this->assertInstanceOf(MissingNode::class, $node = $root->node('vfs://dir.lnk/foo/bar'));
        $this->assertSame(['foo', 'bar'], $node->missingSegments());
        $this->assertInstanceOf(MissingNode::class, $node = $root->node('vfs://inv.lnk/foo'));
        $this->assertSame(['baz', 'foo'], $node->missingSegments());
    }

    public function test_node_for_invalid_path_returns_InvalidNode(): void
    {
        $root = $this->root();
        $this->assertInstanceOf(InvalidNode::class, $node = $root->node('vfs://bar.txt/baz'));
        $this->assertSame(['baz'], $node->missingSegments());
        $this->assertInstanceOf(InvalidNode::class, $node = $root->node('vfs://bar.txt/bar/baz'));
        $this->assertSame(['bar', 'baz'], $node->missingSegments());
        $this->assertInstanceOf(InvalidNode::class, $node = $root->node('vfs://foo/file.lnk/baz'));
        $this->assertSame(['baz'], $node->missingSegments());
    }

    public function test_node_for_existing_path_returns_parent_context_path(): void
    {
        $root = $this->root();
        $this->assertInstanceOf(ParentContext::class, $node = $root->node('vfs://foo/bar/baz.txt'));
        $this->assertTrue($node->isFile());
        $this->assertInstanceOf(ParentContext::class, $node = $root->node('vfs://foo/bar'));
        $this->assertTrue($node->isDir());
        $this->assertInstanceOf(ParentContext::class, $node = $root->node('vfs://foo/file.lnk'));
        $this->assertTrue($node->isFile() && $node->isLink());
        $this->assertInstanceOf(ParentContext::class, $node = $root->node('vfs://dir.lnk'));
        $this->assertTrue($node->isDir() && $node->isLink());
        $this->assertInstanceOf(ParentContext::class, $node = $root->node('vfs://inv.lnk'));
        $this->assertTrue(!$node->isFile() && !$node->isDir() && $node->isLink());
        $this->assertInstanceOf(ParentContext::class, $node = $root->node('vfs://red.lnk'));
        $this->assertTrue(!$node->isFile() && $node->isDir() && $node->isLink());
    }

    public function test_resolving_multiple_links(): void
    {
        $root = new Root('vfs://', new Directory([
            'foo' => new Directory([
                'bar' => $dir = new Directory([
                    'file'         => $file = new File('contents'),
                    'red.file.lnk' => $link = new Link('vfs://file.lnk')
                ])
            ]),
            'file.lnk' => new Link('vfs://foo/bar/file'),
            'dir.lnk'  => new Link('vfs://foo')
        ]));

        $expected = new ParentContext(new LinkedNode($link, $file), $dir, 'red.file.lnk');
        $this->assertEquals($expected, $node = $root->node('vfs://dir.lnk/bar/red.file.lnk'));
        $this->assertSame('contents', $node->contents());
    }

    public function test_circular_reference_protection(): void
    {
        $root = new Root('vfs://', new Directory([
            'foo' => new Directory([
                'lnk.foo' => new Link('vfs://foo/bar/lnk.bar'),
                'bar' => $dir = new Directory([
                    'lnk.bar' => $link = new Link('vfs://lnk.root')
                ])
            ]),
            'lnk.root' => new Link('vfs://foo/lnk.foo')
        ]));

        $expected = new ParentContext(new LinkedNode($link, $link), $dir, 'lnk.bar');
        $this->assertEquals($expected, $node = $root->node('vfs://foo/bar/lnk.bar'));
        $this->assertTrue($node->isValid() && $node->isLink() && !$node->isFile() && !$node->isDir());

        $this->assertEquals($link->node('path'), $root->node('vfs://foo/bar/lnk.bar/path'));
    }

    private function root(): Root
    {
        return new Root('vfs://', new Directory([
            'foo' => new Directory([
                'bar'      => new Directory(['baz.txt' => new File('baz contents')]),
                'file.lnk' => new Link('vfs://bar.txt'),
                'empty'    => new Directory()
            ]),
            'bar.txt' => new File('this is bar file'),
            'dir.lnk' => new Link('vfs://foo/bar'),
            'inv.lnk' => new Link('vfs://foo/baz'),
            'red.lnk' => new Link('vfs://dir.lnk')
        ]));
    }
}
