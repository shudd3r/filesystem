<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Tests\Fixtures\TestRoot;

use Shudd3r\Filesystem\Tests\Fixtures\TestRoot;
use Shudd3r\Filesystem\Virtual\VirtualDirectory;
use Shudd3r\Filesystem\Virtual\VirtualNode;
use Shudd3r\Filesystem\Virtual\Root;
use Shudd3r\Filesystem\Generic\Pathname;
use PHPUnit\Framework\Assert;


class VirtualTestRoot extends TestRoot
{
    private Root\TreeNode\Directory $tree;

    public function __construct(Pathname $rootPath, array $structure = [])
    {
        $this->tree = $this->createNodes($structure);
        parent::__construct(VirtualDirectory::root($rootPath->absolute(), $this->tree), $rootPath);
    }

    public function node(string $name = '', bool $typeMatch = true): VirtualNode
    {
        $root     = new Root($this->rootDir->pathname(), $this->tree);
        $pathname = $this->pathname($name);
        return new class($root, $pathname, $typeMatch) extends VirtualNode {
            private bool $typeMatch;

            public function __construct(Root $root, Pathname $path, bool $typeMatch = true)
            {
                parent::__construct($root, $path);
                $this->typeMatch = $typeMatch;
            }

            protected function nodeExists(Root\TreeNode $node): bool
            {
                return $node->exists() && $this->typeMatch;
            }
        };
    }

    public function assertStructure(array $structure, string $message = ''): void
    {
        Assert::assertEquals($this->createNodes($structure), $this->tree, $message);
    }

    private function createNodes(array $tree): Root\TreeNode\Directory
    {
        foreach ($tree as &$value) {
            $value = is_array($value) ? $this->createNodes($value) : $this->leafNode($value);
        }
        return new Root\TreeNode\Directory($tree);
    }

    private function leafNode(string $value): Root\TreeNode
    {
        $path = str_starts_with($value, '@') ? 'vfs://' . substr($value, 1) : null;
        return $path ? new Root\TreeNode\Link($path) : new Root\TreeNode\File($value);
    }
}
