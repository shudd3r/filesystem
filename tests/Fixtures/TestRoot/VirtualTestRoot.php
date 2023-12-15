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

use Shudd3r\Filesystem\Virtual\VirtualNode;
use Shudd3r\Filesystem\Virtual\VirtualDirectory;
use Shudd3r\Filesystem\Virtual\Nodes;
use Shudd3r\Filesystem\Virtual\Nodes\TreeNode\Directory;
use Shudd3r\Filesystem\Virtual\Nodes\TreeNode\File;
use Shudd3r\Filesystem\Virtual\Nodes\TreeNode\Link;
use Shudd3r\Filesystem\Node;
use Shudd3r\Filesystem\Generic\Pathname;
use Shudd3r\Filesystem\Tests\Fixtures\TestRoot;
use Shudd3r\Filesystem\Tests\Doubles\FakeVirtualNode;
use PHPUnit\Framework\Assert;


class VirtualTestRoot extends TestRoot
{
    private Directory $tree;
    private array     $access;

    public function __construct(?Pathname $rootPath = null, array $structure = [], array $access = [])
    {
        $this->access = $access;
        $this->tree   = $this->createNodes($structure);

        $rootPath = $rootPath ? $rootPath->asRoot() : Pathname::root('vfs://');
        parent::__construct(VirtualDirectory::root($rootPath, $this->tree), $rootPath);
    }

    public function node(string $name = '', bool $typeMatch = true): VirtualNode
    {
        $root = new Nodes($this->rootPath, $this->tree);
        return new FakeVirtualNode($root, $this->pathname($name), $typeMatch);
    }

    public function assertStructure(array $structure, string $message = ''): void
    {
        Assert::assertEquals($this->createNodes($structure), $this->tree, $message);
    }

    private function createNodes(array $tree, string $path = ''): Directory
    {
        foreach ($tree as $name => &$value) {
            $pathName = $path ? $path . '/' . $name : $name;
            $value    = is_array($value) ? $this->createNodes($value, $pathName) : $this->leafNode($value, $pathName);
        }

        return new Directory($tree, $this->mode($path));
    }

    private function leafNode(string $value, string $path): Nodes\TreeNode
    {
        $linked = str_starts_with($value, '@') ? substr($value, 1) : null;
        return $linked ? new Link($linked) : new File($value, $this->mode($path));
    }

    private function mode(string $path): int
    {
        return $this->access[$path] ?? (Node::READ | Node::WRITE);
    }
}
