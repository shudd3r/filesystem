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
use Shudd3r\Filesystem\Virtual\Nodes\Node;
use Shudd3r\Filesystem\Virtual\Nodes\TreeNode\Directory;
use Shudd3r\Filesystem\Virtual\Nodes\RootContext;
use LogicException;


class Nodes
{
    private Pathname  $rootPath;
    private Directory $rootNode;

    /**
     * @param Pathname   $rootPath
     * @param ?Directory $rootNode
     */
    public function __construct(Pathname $rootPath, Directory $rootNode = null)
    {
        $this->rootPath = $rootPath;
        $this->rootNode = $rootNode ?? new Directory();
    }

    /**
     * @param string $path Absolute filesystem path
     *
     * @return Node
     */
    public function node(string $path): Node
    {
        if (!$pathname = $this->rootPath->asRootFor($path)) {
            throw new LogicException();
        }

        $internalPath = str_replace($this->rootPath->separator(), '/', $pathname->relative());
        $rootContext  = new RootContext($this->rootPath, $this->rootNode);
        return $rootContext->nodeAtPath($internalPath);
    }
}
