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
use Shudd3r\Filesystem\Virtual\VirtualFile;
use Shudd3r\Filesystem\Virtual\VirtualLink;
use Shudd3r\Filesystem\Virtual\Root\TreeNode;


abstract class VirtualFilesystemTests extends FilesystemTests
{
    protected VirtualDirectory $root;

    protected function setUp(): void
    {
        $this->root = $this->directory('', $this->exampleStructure());
    }

    protected function directory(string $name = '', array $structure = null): VirtualDirectory
    {
        $structure = $this->createNodes($structure ?? $this->exampleStructure());
        $root      = VirtualDirectory::root('vfs://', $structure);
        return $name ? $this->root->subdirectory($name) : $root;
    }

    protected function file(string $name): VirtualFile
    {
        return $this->root->file($name);
    }

    protected function link(string $name): VirtualLink
    {
        return $this->root->link($name);
    }

    protected function exampleStructure(array $changes = []): array
    {
        $tree = $this->mergeStructure([
            'foo' => [
                'bar'      => ['baz.txt' => 'baz contents'],
                'file.lnk' => 'vfs://bar.txt',
                'empty'    => []
            ],
            'bar.txt' => 'bar contents',
            'dir.lnk' => 'vfs://foo/bar',
            'inv.lnk' => 'vfs://foo/baz'
        ], $changes);

        return $tree;
    }

    private function mergeStructure($tree, $changes): array
    {
        foreach ($changes as $name => $value) {
            $merge = is_array($value) && isset($tree[$name]);
            $tree[$name] = $merge ? $this->mergeStructure($tree[$name], $value) : $value;
            if ($value === null) { unset($tree[$name]); }
        }

        return $tree;
    }

    private function createNodes(array $tree): TreeNode\Directory
    {
        foreach ($tree as $name => &$value) {
            $value = is_array($value) ? $this->createNodes($value) : $this->leafNode($name, $value);
        }
        return new TreeNode\Directory($tree);
    }

    private function leafNode(string $name, string $value): TreeNode
    {
        return str_ends_with($name, '.lnk') ? new TreeNode\Link($value) : new TreeNode\File($value);
    }
}
