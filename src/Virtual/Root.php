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
use Shudd3r\Filesystem\Virtual\Root\TreeNode;
use Shudd3r\Filesystem\Virtual\Root\TreeNode\Directory;
use Shudd3r\Filesystem\Virtual\Root\TreeNode\InvalidNode;
use Shudd3r\Filesystem\Virtual\Root\TreeNode\Link;
use Shudd3r\Filesystem\Virtual\Root\TreeNode\LinkedNode;
use Shudd3r\Filesystem\Virtual\Root\TreeNode\ParentContext;
use Shudd3r\Filesystem\Virtual\Root\TreeNode\PathContext;
use Shudd3r\Filesystem\Exception;


class Root
{
    private Pathname  $path;
    private Directory $directory;

    private array $segments;

    /**
     * @param Pathname   $path
     * @param ?Directory $directory
     */
    public function __construct(Pathname $path, Directory $directory = null)
    {
        $this->path      = $path;
        $this->directory = $directory ?? new Directory();
    }

    public function node(string $path): TreeNode
    {
        if (!$this->segments = $this->pathSegments($path)) {
            return $this->pathContext($this->directory, $path);
        }

        $basename = array_pop($this->segments);
        $parent   = $this->directory->node(...$this->segments);
        if ($parent instanceof Link) {
            $parent = $this->targetNode($parent);
        }
        $this->segments[] = $basename;

        $node = $parent->node($basename);
        if ($node instanceof Link) {
            $node = new LinkedNode($node, $this->targetNode($node));
        }
        if ($parent instanceof Directory && ($node->exists() || $node->isLink())) {
            $node = new ParentContext($node, $parent, $basename);
        }
        return $this->pathContext($node, $path);
    }

    private function pathContext(TreeNode $node, string $path): PathContext
    {
        return new PathContext($this->path, $node, $path, $this->resolvedPath()->absolute());
    }

    private function targetNode(Link $node): TreeNode
    {
        $resolved = [];
        do {
            $target   = $node->target();
            $segments = $target ? explode('/', $target) : [];
            $linked   = $this->directory->node(...$segments);
            $invalid  = !$linked->exists() || in_array($target, $resolved, true);
            if ($invalid) {
                $segments = $resolved ? explode('/', array_pop($resolved)) : $this->segments;
                $linked   = new InvalidNode();
            }
            $expand = $node->missingSegments();
            $node   = $expand ? $linked->node(...$expand) : $linked;
            $resolved[] = $target;
        } while ($node instanceof Link);

        $this->segments = [...$segments, ...$expand];
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

    private function resolvedPath(): Pathname
    {
        $name = implode('/', $this->segments);
        return $name ? $this->path->forChildNode($name) : $this->path;
    }
}
