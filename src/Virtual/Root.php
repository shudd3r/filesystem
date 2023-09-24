<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Virtual;

use Shudd3r\Filesystem\Virtual\TreeNode\Directory;
use Shudd3r\Filesystem\Exception\UnsupportedOperation;


class Root
{
    private string    $path;
    private Directory $nodes;
    private int       $length;

    /**
     * @param string     $path
     * @param ?Directory $nodes
     */
    public function __construct(string $path, Directory $nodes = null)
    {
        $this->path   = $path;
        $this->nodes  = $nodes ?? new Directory();
        $this->length = strlen($path) + (str_ends_with($path, '/') ? 0 : 1);
    }

    public function node(string $path): TreeNode
    {
        return $this->nodes->node($this->relativePath($path));
    }

    private function relativePath(string $path): string
    {
        if (!str_starts_with($path, $this->path)) {
            throw new UnsupportedOperation();
        }

        return substr($path, $this->length);
    }
}
