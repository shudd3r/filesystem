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

use Shudd3r\Filesystem\Tests\FilesystemTests;
use Shudd3r\Filesystem\Virtual\VirtualDirectory;
use Shudd3r\Filesystem\Generic\Pathname;
use Shudd3r\Filesystem\Directory;
use Shudd3r\Filesystem\Virtual\Root\TreeNode;
use Shudd3r\Filesystem\Tests\Doubles\FakeNodes;


abstract class VirtualFilesystemTests extends FilesystemTests
{
    protected function root(array $structure = null): VirtualDirectory
    {
        $structure = $this->createNodes($structure ?? $this->exampleStructure());
        return VirtualDirectory::root('vfs://', $structure);
    }

    protected function nodes(array $structure = []): FakeNodes
    {
        return new FakeNodes\FakeVirtualNodes($this->createNodes($structure), Pathname::root('vfs://'));
    }

    protected function path(string $name = ''): string
    {
        return 'vfs://' . $name;
    }

    protected function assertSameStructure(Directory $root, array $structure = null, string $message = ''): void
    {
        $this->assertEquals($this->root($structure ?? $this->exampleStructure()), $root, $message);
    }

    protected function createNodes(array $tree): TreeNode\Directory
    {
        foreach ($tree as &$value) {
            $value = is_array($value) ? $this->createNodes($value) : $this->leafNode($value);
        }
        return new TreeNode\Directory($tree);
    }

    private function leafNode(string $value): TreeNode
    {
        $path = str_starts_with($value, '@') ? 'vfs://' . substr($value, 1) : null;
        return $path ? new TreeNode\Link($path) : new TreeNode\File($value);
    }
}
