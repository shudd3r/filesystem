<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Tests\Virtual\Root;

use PHPUnit\Framework\TestCase;
use Shudd3r\Filesystem\Virtual\Root\TreeNode;
use Shudd3r\Filesystem\Exception;


class TreeNodeTest extends TestCase
{
    public function test_default_methods(): void
    {
        $node = new class() extends TreeNode {
        };
        $this->assertException(fn () => $node->node('foo'));
        $this->assertTrue($node->exists());
        $this->assertFalse($node->isDir());
        $this->assertFalse($node->isFile());
        $this->assertFalse($node->isLink());
        $this->assertTrue($node->isValid());
        $this->assertSame([], iterator_to_array($node->filenames()));
        $this->assertException(fn () => $node->remove());
        $this->assertEmpty($node->contents());
        $this->assertException(fn () => $node->putContents('contents...'));
        $this->assertNull($node->target());
        $this->assertException(fn () => $node->setTarget('foo'));
        $this->assertSame([], $node->missingSegments());
    }

    private function assertException(callable $methodCall): void
    {
        $expected = Exception\UnsupportedOperation::class;
        try {
            $methodCall();
        } catch (\Exception $ex) {
            $message = 'Unexpected Exception type - expected `%s` caught `%s`';
            $this->assertInstanceOf($expected, $ex, sprintf($message, $expected, get_class($ex)));
            return;
        }

        $this->fail(sprintf('No Exception thrown - expected `%s`', $expected));
    }
}
