<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Virtual\TreeNode;

use Shudd3r\Filesystem\Virtual\TreeNode;
use Shudd3r\Filesystem\Exception\UnsupportedOperation;


class Root extends Directory
{
    private string $path;
    private int    $rootLength;

    /**
     * @param string                  $path
     * @param array<string, TreeNode> $nodes
     */
    public function __construct(string $path, array $nodes = [])
    {
        $this->path       = $path;
        $this->rootLength = strlen($path) + (str_ends_with($path, '/') ? 0 : 1);
        parent::__construct($nodes);
    }

    public function node(string $path): TreeNode
    {
        return parent::node($this->relativePath($path));
    }

    public function add(string $path, TreeNode $node): void
    {
        parent::add($this->relativePath($path), $node);
    }

    public function remove(string $path): void
    {
        parent::remove($this->relativePath($path));
    }

    private function relativePath(string $path): string
    {
        if (!str_starts_with($path, $this->path)) {
            throw new UnsupportedOperation();
        }

        return substr($path, $this->rootLength);
    }
}
