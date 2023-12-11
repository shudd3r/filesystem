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
use Shudd3r\Filesystem\Virtual\Root\TreeNode\Directory;
use Shudd3r\Filesystem\Virtual\Root\TreeNode;
use Shudd3r\Filesystem\Exception;


class Root
{
    private Pathname  $path;
    private Directory $nodes;

    private string $foundPath;
    private string $realPath;

    /**
     * @param Pathname   $path
     * @param ?Directory $nodes
     */
    public function __construct(Pathname $path, Directory $nodes = null)
    {
        $this->path  = $path;
        $this->nodes = $nodes ?? new Directory();
    }

    public function node(string $path): TreeNode
    {
        $segments = $this->pathSegments($path);
        $this->foundPath = $path;
        $this->realPath  = $path;

        if (!$segments) { return $this->pathContext($this->nodes); }

        $basename = array_pop($segments);
        $parent   = $segments ? $this->nodes->node(...$segments) : $this->nodes;
        if ($parent instanceof TreeNode\Link) {
            $parent = $this->targetNode($parent);
            $this->setRealPath($this->realPath, $basename);
        }

        $node = $parent->node($basename);
        if ($node instanceof TreeNode\Link) {
            $node = new TreeNode\LinkedNode($node, $this->targetNode($node));
        }
        if ($node->exists() || $node->isLink()) {
            $node = new TreeNode\ParentContext($node, $parent, $basename);
        }
        return $this->pathContext($node);
    }

    private function pathContext(TreeNode $node): TreeNode\PathContext
    {
        return new TreeNode\PathContext($node, $this->foundPath, $this->realPath);
    }

    private function targetNode(TreeNode\Link $node): TreeNode
    {
        $expand = [];
        $path   = $this->realPath;

        $resolvedPaths = [];
        while ($node instanceof TreeNode\Link) {
            $path    = $node->target();
            $expand  = $node->missingSegments();
            $linked  = $this->nodes->node(...$this->pathSegments($path));
            $invalid = !$linked->exists() || in_array($path, $resolvedPaths, true);
            if ($invalid) {
                $path   = $resolvedPaths ? array_pop($resolvedPaths) : $this->realPath;
                $linked = new TreeNode\InvalidNode();
            }
            $node = $expand ? $linked->node(...$expand) : $linked;
            $resolvedPaths[] = $path;
        }

        $this->setRealPath($path, ...$expand);
        return $node;
    }

    private function pathSegments(string $path): array
    {
        if (!$pathname = $this->path->asRootFor($path)) {
            throw new Exception\UnsupportedOperation();
        }

        $path = $pathname->relative();
        return $path ? explode($this->path->separator(), $path) : [];
    }

    private function setRealPath(string $path, string ...$segments): void
    {
        $pathname = $this->path->asRootFor($path);
        $this->realPath = $segments
            ? $pathname->forChildNode(implode($this->path->separator(), $segments))->absolute()
            : $pathname->absolute();
    }
}
