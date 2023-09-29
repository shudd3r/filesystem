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
        if ($parent instanceof TreeNode\Link) {
            $parent = $this->targetNode($parent);
        }

        if (!$parent->isDir()) { return $parent->node($basename); }

        $node = $parent->node($basename);
        if ($node instanceof TreeNode\Link) {
            $node = new TreeNode\LinkedNode($node, $this->targetNode($node));
        }

        return $node->exists() || $node->isLink() ? new TreeNode\ParentContext($node, $parent, $basename) : $node;
    }

    private function targetNode(TreeNode\Link $link): TreeNode
    {
        $path = $this->pathSegments($link->target());
        $node = $this->nodes->node(...$path, ...$link->missingSegments());
        return $node instanceof TreeNode\Link ? $this->targetNode($node) : $node;
    }

    private function pathSegments(string $path): array
    {
        if (!str_starts_with($path, $this->path)) {
            throw new Exception\UnsupportedOperation();
        }

        $path = substr($path, $this->length);
        return $path ? explode('/', $path) : [];
    }
}
