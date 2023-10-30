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
use Shudd3r\Filesystem\Virtual\Root\TreeNode;


abstract class VirtualFilesystemTests extends FilesystemTests
{
    protected function root(array $structure = null): VirtualDirectory
    {
        $structure = $this->createNodes($structure ?? $this->exampleStructure());
        return VirtualDirectory::root('vfs://', $structure);
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
