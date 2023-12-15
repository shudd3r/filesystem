<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Virtual\Nodes;

use Shudd3r\Filesystem\Generic\Pathname;
use Shudd3r\Filesystem\Virtual\Nodes\TreeNode\Directory;
use Shudd3r\Filesystem\Virtual\Nodes\TreeNode\InvalidNode;
use Shudd3r\Filesystem\Virtual\Nodes\TreeNode\Link;
use Shudd3r\Filesystem\Virtual\Nodes\TreeNode\LinkedNode;
use Shudd3r\Filesystem\Virtual\Nodes\TreeNode\ParentContext;


class RootContext
{
    private Pathname $rootPath;
    private TreeNode $rootNode;

    private array $segments;

    /**
     * Class resolving TreeNode composition and path resolution for
     * requested Node instance.
     */
    public function __construct(Pathname $rootPath, Directory $rootNode)
    {
        $this->rootPath = $rootPath;
        $this->rootNode = $rootNode;
    }

    /**
     * @param string $path Absolute TreeNode path (without filesystem root)
     *
     * @return Node found at given path
     */
    public function nodeAtPath(string $path): Node
    {
        if (!$this->segments = $path ? explode('/', $path) : []) {
            return $this->pathContext($this->rootNode, $path);
        }

        $basename = array_pop($this->segments);
        $parent   = $this->rootNode->node(...$this->segments);
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

    private function pathContext(TreeNode $node, $path): Node
    {
        $originalPath = $path ? $this->rootPath->forChildNode($path) : $this->rootPath;
        return new Node($this->rootPath, $node, $originalPath->absolute(), $this->resolvedPath());
    }

    private function targetNode(Link $node): TreeNode
    {
        $resolved = [];
        do {
            $target   = $node->target();
            $segments = $target ? explode('/', $target) : [];
            $linked   = $this->rootNode->node(...$segments);
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

    private function resolvedPath(): string
    {
        $name = implode('/', $this->segments);
        $path = $name ? $this->rootPath->forChildNode($name) : $this->rootPath;
        return $path->absolute();
    }
}
