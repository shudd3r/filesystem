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
use Shudd3r\Filesystem\Virtual\TreeNode\InvalidNode;


class InvalidNodeTest extends TestCase
{
    public function test_isValid_method_returns_false(): void
    {
        $this->assertFalse($this->node('foo/bar')->isValid());
    }

    public function test_exists_method_returns_false(): void
    {
        $this->assertFalse($this->node('foo/bar')->exists());
    }

    public function test_missingPath_method_returns_instance_path(): void
    {
        $this->assertSame('foo/bar', $this->node('foo/bar')->missingPath());
    }

    private function node(string $missingPath): InvalidNode
    {
        return new InvalidNode($missingPath);
    }
}
