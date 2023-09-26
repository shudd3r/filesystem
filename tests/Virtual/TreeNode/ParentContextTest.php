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
use Shudd3r\Filesystem\Virtual\TreeNode\ParentContext;
use Shudd3r\Filesystem\Virtual\TreeNode;


class ParentContextTest extends TestCase
{
    public function test_remove_method_removes_node_from_parent_directory(): void
    {
        $directory = new TreeNode\Directory(['foo' => new TreeNode\File('contents...')]);
        $this->context(new TreeNode\File('contents...'), $directory, 'foo')->remove();
        $this->assertEquals(new TreeNode\Directory(), $directory);
    }

    public function test_other_methods_are_delegated_to_wrapped_node(): void
    {
        $node    = new TreeNode\Directory(['file.txt' => new TreeNode\File('foo file')]);
        $context = $this->context($node, new TreeNode\Directory(), 'foo');
        $this->assertTrue($context->exists());
        $this->assertTrue($context->isDir());
        $this->assertTrue($context->isValid());
        $this->assertSame(['file.txt'], iterator_to_array($context->filenames(), false));

        $node    = new TreeNode\File('foo file');
        $context = $this->context($node, new TreeNode\Directory(), 'foo');
        $this->assertTrue($context->isFile());
        $this->assertSame('foo file', $context->contents());
        $context->putContents('new contents');
        $this->assertSame('new contents', $context->contents());

        $node    = new TreeNode\Link('vfs://node/path');
        $context = $this->context($node, new TreeNode\Directory(), 'foo');
        $this->assertTrue($context->isLink());
        $this->assertSame('vfs://node/path', $context->target());
        $context->setTarget('vfs://new/node');
        $this->assertSame('vfs://new/node', $context->target());

        $node    = new TreeNode\InvalidNode('foo', 'bar');
        $context = $this->context($node, new TreeNode\Directory(), 'foo');
        $this->assertFalse($context->exists());
        $this->assertFalse($context->isDir());
        $this->assertFalse($context->isFile());
        $this->assertFalse($context->isLink());
        $this->assertFalse($context->isValid());
    }

    private function context(TreeNode $node, TreeNode\Directory $parent, string $name): ParentContext
    {
        return new ParentContext($node, $parent, $name);
    }
}
