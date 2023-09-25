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
use Shudd3r\Filesystem\Virtual\TreeNode\File;
use Shudd3r\Filesystem\Virtual\TreeNode\InvalidNode;


class FileTest extends TestCase
{
    public function test_node_method_returns_InvalidNode_with_given_path(): void
    {
        $this->assertEquals(new InvalidNode('foo', 'bar'), $this->file()->node('foo', 'bar'));
    }

    public function test_isFile_method_returns_true(): void
    {
        $this->assertTrue($this->file()->isFile());
    }

    public function test_contents_method_returns_file_contents(): void
    {
        $this->assertSame('contents...', $this->file('contents...')->contents());
    }

    public function test_putContents_method_changes_file_contents(): void
    {
        $file = $this->file('old contents...');
        $file->putContents('new contents...');
        $this->assertSame('new contents...', $file->contents());
    }

    private function file(string $contents = ''): File
    {
        return new File($contents);
    }
}
