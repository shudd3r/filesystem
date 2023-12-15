<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Tests\Virtual\Nodes;

use PHPUnit\Framework\TestCase;
use Shudd3r\Filesystem\Virtual\Nodes\TreeNode;
use Shudd3r\Filesystem\Node;
use LogicException;
use Exception;


class TreeNodeTest extends TestCase
{
    public function test_default_methods(): void
    {
        $node = new class() extends TreeNode {
        };
        $this->assertInstanceOf(TreeNode\InvalidNode::class, $node->node('foo'));
        $this->assertTrue($node->exists());
        $this->assertFalse($node->isDir());
        $this->assertFalse($node->isFile());
        $this->assertFalse($node->isLink());
        $this->assertTrue($node->isValid());
        $this->assertSame([], iterator_to_array($node->filenames()));
        $this->assertException(fn () => $node->remove());
        $this->assertException(fn () => $node->createDir());
        $this->assertEmpty($node->contents());
        $this->assertException(fn () => $node->putContents('contents...'));
        $this->assertNull($node->target());
        $this->assertException(fn () => $node->setTarget('foo'));
        $this->assertSame([], $node->missingSegments());
        $this->assertException(fn () => $node->moveTo($node));
        $this->assertTrue($node->isAllowed(Node::READ | Node::WRITE | Node::REMOVE));
    }

    private function assertException(callable $methodCall): void
    {
        try {
            $methodCall();
        } catch (Exception $ex) {
            $this->assertInstanceOf(LogicException::class, $ex, 'Unexpected Exception type');
            return;
        }

        $this->fail(sprintf('No Exception thrown - expected `%s`', LogicException::class));
    }
}
