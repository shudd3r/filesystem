<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Tests\Doubles\FakeNodes;

use Shudd3r\Filesystem\Tests\Doubles\FakeNodes;
use Shudd3r\Filesystem\Generic\Pathname;
use Shudd3r\Filesystem\Virtual\Root;
use Shudd3r\Filesystem\Virtual\Root\TreeNode\Directory;
use Shudd3r\Filesystem\Virtual\VirtualNode;


class FakeVirtualNodes implements FakeNodes
{
    private Directory $rootDir;
    private Pathname  $rootPath;

    public function __construct(Directory $rootDir, Pathname $rootPath)
    {
        $this->rootDir  = $rootDir;
        $this->rootPath = $rootPath;
    }

    public function node(string $name = '', bool $typeMatch = true): VirtualNode
    {
        $root     = new Root($this->rootPath->absolute(), $this->rootDir);
        $pathname = $name ? $this->rootPath->forChildNode($name) : $this->rootPath;
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
}
