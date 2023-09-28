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
use Shudd3r\Filesystem\Virtual\TreeNode\Link;
use Shudd3r\Filesystem\Virtual\TreeNode\LinkedNode;
use Shudd3r\Filesystem\Virtual\TreeNode\ParentContext;
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
        $path = $this->pathSegments($path);
        if (!$path) { return $this->nodes; }

        $basename = array_pop($path);
        $parent   = $path ? $this->nodes->node(...$path) : $this->nodes;
        if ($parent instanceof Link) {
            $parent = $this->targetNode($parent);
        }

        if (!$parent->isDir()) { return $parent->node($basename); }

        $node = $parent->node($basename);
        if ($node instanceof Link) {
            $node = new LinkedNode($node, $this->targetNode($node));
        }

        return $node->exists() || $node->isLink() ? new ParentContext($node, $parent, $basename) : $node;
    }

    private function targetNode(Link $link): TreeNode
    {
        $path = $this->pathSegments($link->target());
        return $this->nodes->node(...$path, ...$link->missingSegments());
    }

    private function pathSegments(string $path): array
    {
        if (!str_starts_with($path, $this->path)) {
            throw new UnsupportedOperation();
        }

        $path = substr($path, $this->length);
        return $path ? explode('/', $path) : [];
    }
}
