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
use Shudd3r\Filesystem\Virtual\Root\TreeNode\ParentContext;
use Shudd3r\Filesystem\Virtual\Root\TreeNode;
use Shudd3r\Filesystem\Exception;


class ParentContextTest extends TestCase
{
    public static function nonContextNodes(): array
    {
        return [
            [new TreeNode\File()],
            [new TreeNode\Directory()],
            [new TreeNode\Link('vfs://')],
            [new TreeNode\InvalidNode()],
            [new TreeNode\MissingNode(new TreeNode\Directory())],
            [new TreeNode\LinkedNode(new TreeNode\Link('vfs://file.txt'), new TreeNode\File())]
        ];
    }

    public function test_remove_method_removes_node_from_parent_directory(): void
    {
        $directory = new TreeNode\Directory(['foo' => new TreeNode\File('contents...')]);
        $this->context(new TreeNode\File('contents...'), $directory)->remove();
        $this->assertEquals(new TreeNode\Directory(), $directory);
    }

    /**
     * @dataProvider nonContextNodes
     *
     * @param TreeNode $node
     */
    public function test_pull_from_non_context_node_throws_Exception(TreeNode $node): void
    {
        $this->expectException(Exception\UnsupportedOperation::class);
        $this->context(new TreeNode\File(), new TreeNode\Directory())->pull($node);
    }

    public function test_pull_replaces_directory_node(): void
    {
        $parent = new TreeNode\Directory([
            'foo' => $foo = new TreeNode\File('moved'),
            'bar' => $bar = new TreeNode\File('replaced')
        ]);

        $node    = new ParentContext($foo, $parent, 'foo');
        $context = new ParentContext($bar, $parent, 'bar');
        $context->pull($node);

        $this->assertEquals(new TreeNode\Directory(['bar' => $foo]), $parent);
    }

    public function test_pull_replaces_directory_link_node(): void
    {
        $parent = new TreeNode\Directory([
            'bar'     => $bar = new TreeNode\File('replaced'),
            'foo.lnk' => $foo = new TreeNode\Link('overwritten'),
            'baz.lnk' => $baz = new TreeNode\Link('moved')
        ]);

        $node    = new ParentContext(new TreeNode\LinkedNode($baz, $this->stub()), $parent, 'baz.lnk');
        $context = new ParentContext(new TreeNode\LinkedNode($foo, $this->stub()), $parent, 'foo.lnk');
        $context->pull($node);
        $this->assertEquals(new TreeNode\Directory(['bar' => $bar, 'foo.lnk' => $baz]), $parent);

        $node    = new ParentContext(new TreeNode\LinkedNode($baz, $this->stub()), $parent, 'foo.lnk');
        $context = new ParentContext($bar, $parent, 'bar');
        $context->pull($node);
        $this->assertEquals(new TreeNode\Directory(['bar' => $baz]), $parent);
    }

    public function test_pull_for_currently_assigned_node_is_ignored(): void
    {
        $parent = new TreeNode\Directory([
            'foo' => $foo = new TreeNode\File('foo'),
            'bar' => $bar = new TreeNode\Link('vfs://foo')
        ]);

        $expected = clone $parent;

        $node = $this->context($foo, $parent);
        $this->context($foo, $parent)->pull($node);
        $this->assertEquals($expected, $parent);

        $node = $this->context(new TreeNode\LinkedNode($bar, $this->stub()), $parent);
        $this->context(new TreeNode\LinkedNode($bar, $this->stub()), $parent)->pull($node);
        $this->assertEquals($expected, $parent);
    }

    public function test_methods_delegated_to_wrapped_node(): void
    {
        $node    = new TreeNode\Directory(['file.txt' => new TreeNode\File('foo file')]);
        $context = $this->context($node, new TreeNode\Directory());
        $this->assertTrue($context->exists());
        $this->assertTrue($context->isDir());
        $this->assertTrue($context->isValid());
        $this->assertSame(['file.txt'], iterator_to_array($context->filenames(), false));

        $node    = new TreeNode\File('foo file');
        $context = $this->context($node, new TreeNode\Directory());
        $this->assertTrue($context->isFile());
        $this->assertSame('foo file', $context->contents());
        $context->putContents('new contents');
        $this->assertSame('new contents', $context->contents());

        $node    = new TreeNode\Link('vfs://node/path');
        $context = $this->context($node, new TreeNode\Directory());
        $this->assertTrue($context->isLink());
        $this->assertSame('vfs://node/path', $context->target());
        $context->setTarget('vfs://new/node');
        $this->assertSame('vfs://new/node', $context->target());

        $node    = new TreeNode\InvalidNode('foo', 'bar');
        $context = $this->context($node, new TreeNode\Directory());
        $this->assertFalse($context->exists());
        $this->assertFalse($context->isDir());
        $this->assertFalse($context->isFile());
        $this->assertFalse($context->isLink());
        $this->assertFalse($context->isValid());
    }

    private function context(TreeNode $node, TreeNode\Directory $parent): ParentContext
    {
        return new ParentContext($node, $parent, 'foo');
    }

    private function stub(): TreeNode
    {
        return new TreeNode\File('stub');
    }
}
