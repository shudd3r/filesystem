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

    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__) . '/Fixtures/native-override/virtual.php';
    }

    protected function setUp(): void
    {
        $this->root = VirtualDirectory::root('vfs://', $this->exampleStructure());
    }

    protected function directory(string $name = '', TreeNode\Directory $nodes = null): VirtualDirectory
    {
        $root = VirtualDirectory::root('vfs://', $nodes ?? $this->exampleStructure());
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

    protected function exampleStructure(array $changes = []): TreeNode\Directory
    {
        $tree = $this->mergeStructure([
            'foo' => [
                'bar' => [
                    'baz.txt' => new TreeNode\File('baz contents')
                ],
                'file.lnk' => new TreeNode\Link('vfs://bar.txt'),
                'empty'    => []
            ],
            'bar.txt' => new TreeNode\File('bar contents'),
            'dir.lnk' => new TreeNode\Link('vfs://foo/bar'),
            'inv.lnk' => new TreeNode\Link('vfs://foo/baz')
        ], $changes);

        return new TreeNode\Directory($this->createDirectories($tree));
    }

    private function mergeStructure($tree, $changes): array
    {
        foreach ($changes as $name => $value) {
            if ($value === null) {
                unset($tree[$name]);
                continue;
            }
            if (!is_array($value)) {
                $tree[$name] = $value;
                continue;
            }
            $tree[$name] = isset($tree[$name]) ? $this->mergeStructure($tree[$name], $value) : $value;
        }

        return $tree;
    }

    private function createDirectories(array $tree): array
    {
        foreach ($tree as &$value) {
            if (!is_array($value)) { continue; }
            $value = new TreeNode\Directory($this->createDirectories($value));
        }
        return $tree;
    }
}
