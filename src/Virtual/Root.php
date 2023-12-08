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

use Shudd3r\Filesystem\Virtual\Root\TreeNode\Directory;
use Shudd3r\Filesystem\Virtual\Root\TreeNode;
use Shudd3r\Filesystem\Exception;


class Root
{
    private string    $path;
    private Directory $nodes;
    private int       $length;

    private string $foundPath;
    private string $realPath;

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
        if (!str_starts_with($path, $this->path)) {
            throw new Exception\UnsupportedOperation();
        }

        $path = substr($path, $this->length);
        return $path ? explode('/', $path) : [];
    }

    private function setRealPath(string $path, string ...$segments): void
    {
        $expand = $segments ? implode('/', $segments) : '';
        if ($expand && !str_ends_with($path, '/')) {
            $expand = '/' . $expand;
        }
        $this->realPath = $path . $expand;
    }
}
