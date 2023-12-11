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
use Shudd3r\Filesystem\Node;
use Shudd3r\Filesystem\Exception;


class ParentContextTest extends TestCase
{
    public static function nonContextNodes(): array
    {
        return [
            [new TreeNode\File()],
            [new TreeNode\Directory()],
            [new TreeNode\Link('')],
            [new TreeNode\InvalidNode()],
            [new TreeNode\LinkedNode(new TreeNode\Link('file.txt'), new TreeNode\File())]
        ];
    }

    public function test_remove_method_removes_node_from_parent_directory(): void
    {
        $directory = new TreeNode\Directory(['foo' => new TreeNode\File('contents...')]);
        $this->node(new TreeNode\File('contents...'), $directory)->remove();
        $this->assertEquals(new TreeNode\Directory(), $directory);
    }

    /**
     * @dataProvider nonContextNodes
     *
     * @param TreeNode $target
     */
    public function test_moveTo_non_context_node_throws_Exception(TreeNode $target): void
    {
        $node = $this->node(new TreeNode\File());
        $this->expectException(Exception\UnsupportedOperation::class);
        $node->moveTo($target);
    }

    public function test_moveTo_not_existing_node_moves_node_within_directory(): void
    {
        $parent = new TreeNode\Directory([
            'foo' => $dir = new TreeNode\Directory(['file' => $file = new TreeNode\File('moved')])
        ]);

        $target = new TreeNode\MissingNode($parent, 'bar');
        $node   = $this->node($file, $dir, 'file');
        $node->moveTo($target);

        $this->assertEquals(new TreeNode\Directory([]), $dir);
        $this->assertEquals(new TreeNode\Directory(['foo' => $dir, 'bar' => $file]), $parent);
    }

    public function test_moveTo_replaces_existing_node(): void
    {
        $parent = new TreeNode\Directory([
            'moved'    => $moved = new TreeNode\File('moved'),
            'replaced' => $replaced = new TreeNode\File('replaced')
        ]);

        $target = $this->node($replaced, $parent, 'replaced');
        $node   = $this->node($moved, $parent, 'moved');
        $node->moveTo($target);

        $this->assertEquals(new TreeNode\Directory(['replaced' => $moved]), $parent);
    }

    public function test_moveTo_replaces_directory_link_node(): void
    {
        $parent = new TreeNode\Directory([
            'bar'     => $bar = new TreeNode\File('replaced'),
            'foo.lnk' => $foo = new TreeNode\Link('overwritten'),
            'baz.lnk' => $baz = new TreeNode\Link('moved')
        ]);

        $node   = $this->node(new TreeNode\LinkedNode($baz, $this->stub()), $parent, 'baz.lnk');
        $target = $this->node(new TreeNode\LinkedNode($foo, $this->stub()), $parent, 'foo.lnk');
        $node->moveTo($target);
        $this->assertEquals(new TreeNode\Directory(['bar' => $bar, 'foo.lnk' => $baz]), $parent);

        $node   = $this->node(new TreeNode\LinkedNode($baz, $this->stub()), $parent, 'foo.lnk');
        $target = $this->node($bar, $parent, 'bar');
        $node->moveTo($target);
        $this->assertEquals(new TreeNode\Directory(['bar' => $baz]), $parent);
    }

    public function test_moveTo_for_currently_assigned_node_is_ignored(): void
    {
        $parent = new TreeNode\Directory([
            'foo' => $foo = new TreeNode\File('foo'),
            'bar' => $bar = new TreeNode\Link('vfs://foo')
        ]);

        $expected = clone $parent;

        $node = $this->node($foo, $parent);
        $this->node($foo, $parent)->moveTo($node);
        $this->assertEquals($expected, $parent);

        $node = $this->node(new TreeNode\LinkedNode($bar, $this->stub()), $parent);
        $this->node(new TreeNode\LinkedNode($bar, $this->stub()), $parent)->moveTo($node);
        $this->assertEquals($expected, $parent);
    }

    public function test_methods_delegated_to_wrapped_node(): void
    {
        $node = $this->node(new TreeNode\Directory(['file.txt' => new TreeNode\File('foo file')]));
        $this->assertTrue($node->exists());
        $this->assertTrue($node->isDir());
        $this->assertTrue($node->isValid());
        $this->assertSame(['file.txt'], iterator_to_array($node->filenames(), false));

        $node = $this->node(new TreeNode\File('foo file'));
        $this->assertTrue($node->isFile());
        $this->assertSame('foo file', $node->contents());
        $node->putContents('new contents');
        $this->assertSame('new contents', $node->contents());

        $node = $this->node(new TreeNode\Link('vfs://node/path'));
        $this->assertTrue($node->isLink());
        $this->assertSame('vfs://node/path', $node->target());
        $node->setTarget('vfs://new/node');
        $this->assertSame('vfs://new/node', $node->target());

        $node = $this->node(new TreeNode\InvalidNode('foo', 'bar'));
        $this->assertFalse($node->exists());
        $this->assertFalse($node->isDir());
        $this->assertFalse($node->isFile());
        $this->assertFalse($node->isLink());
        $this->assertFalse($node->isValid());
    }

    public function test_context_permissions_depend_on_wrapped_node(): void
    {
        $node = $this->node(new TreeNode\File(''));
        $this->assertTrue($node->isAllowed(Node::READ | Node::WRITE));

        $node = $this->node(new TreeNode\File('', Node::READ));
        $this->assertFalse($node->isAllowed(Node::READ | Node::WRITE));
        $this->assertTrue($node->isAllowed(Node::READ));
        $this->assertFalse($node->isAllowed(Node::WRITE));

        $node = $this->node(new TreeNode\File('', Node::WRITE));
        $this->assertFalse($node->isAllowed(Node::READ));
        $this->assertTrue($node->isAllowed(Node::WRITE));
    }

    public function test_remove_permission_depends_on_parent_write_access(): void
    {
        $node = $this->node(new TreeNode\File('foo file'), new TreeNode\Directory([], Node::READ));
        $this->assertFalse($node->isAllowed(Node::REMOVE));
    }

    private function node(TreeNode $node, TreeNode\Directory $parent = null, string $name = 'foo'): ParentContext
    {
        return new ParentContext($node, $parent ?? new TreeNode\Directory(), $name);
    }

    private function stub(): TreeNode
    {
        return new TreeNode\File('stub');
    }
}
