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

use Shudd3r\Filesystem\Generic\Pathname;
use Shudd3r\Filesystem\Virtual\Root\Node;
use Shudd3r\Filesystem\Virtual\Root\TreeNode\Directory;
use Shudd3r\Filesystem\Virtual\Root\RootContext;
use LogicException;


class Root
{
    private Pathname  $path;
    private Directory $directory;

    /**
     * @param Pathname   $path
     * @param ?Directory $directory
     */
    public function __construct(Pathname $path, Directory $directory = null)
    {
        $this->path      = $path;
        $this->directory = $directory ?? new Directory();
    }

    public function node(string $path): Node
    {
        if (!$pathname = $this->path->asRootFor($path)) {
            throw new LogicException();
        }

        $internalPath = str_replace($this->path->separator(), '/', $pathname->relative());
        $rootContext  = new RootContext($this->path, $this->directory);
        return $rootContext->nodeAtPath($internalPath);
    }
}
