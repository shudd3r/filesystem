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
use Shudd3r\Filesystem\Node;
use Shudd3r\Filesystem\Virtual\Root\TreeNode\MissingNode;
use Shudd3r\Filesystem\Virtual\Root\TreeNode\Directory;
use Shudd3r\Filesystem\Virtual\Root\TreeNode\File;
use Shudd3r\Filesystem\Virtual\Root\TreeNode\Link;


class MissingNodeTest extends TestCase
{
    public function test_exists_method_returns_false(): void
    {
        $this->assertFalse($this->missingNode($directory, 'foo')->exists());
    }

    public function test_missingSegments_method_returns_instance_segments(): void
    {
        $this->assertSame(['foo', 'bar'], $this->missingNode($directory, 'foo', 'bar')->missingSegments());
    }

    public function test_node_method_returns_new_instance_with_expanded_missing_path_segments(): void
    {
        $missingNode = $this->missingNode($directory, 'foo');
        $this->assertNotSame($missingNode, $missingNode->node('bar', 'baz'));
        $this->assertEquals($this->missingNode($directory, 'foo', 'bar', 'baz'), $missingNode->node('bar', 'baz'));
    }

    public function test_createDir_creates_new_subdirectory(): void
    {
        $this->missingNode($directory, 'foo', 'bar')->createDir();
        $this->assertEquals(new Directory(), $directory->node('foo', 'bar'));
    }

    public function test_putContents_creates_File_in_directory(): void
    {
        $this->missingNode($directory, 'foo', 'bar')->putContents('file contents...');
        $this->assertEquals(new File('file contents...'), $directory->node('foo', 'bar'));
    }

    public function test_setTarget_creates_Link_in_directory(): void
    {
        $this->missingNode($directory, 'foo', 'bar.lnk')->setTarget('vfs://foo/bar');
        $this->assertEquals(new Link('vfs://foo/bar'), $directory->node('foo', 'bar.lnk'));
    }

    public function test_isAllowed_returns_permissions_of_parent_directory(): void
    {
        $parent = new Directory([], Node::WRITE);
        $node   = $this->missingNode($parent, 'foo');
        $this->assertTrue($node->isAllowed(Node::WRITE));
        $this->assertFalse($node->isAllowed(Node::READ));
    }

    public function test_remove_permission_depends_on_directory_write_access(): void
    {
        $parent = new Directory([], Node::READ);
        $node   = $this->missingNode($parent, 'foo');
        $this->assertFalse($node->isAllowed(Node::REMOVE));
    }

    private function missingNode(Directory &$directory = null, string ...$missingSegments): MissingNode
    {
        $directory ??= new Directory();
        return new MissingNode($directory, ...$missingSegments);
    }
}
