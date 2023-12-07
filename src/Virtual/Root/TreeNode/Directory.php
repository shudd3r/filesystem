<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Virtual\Root\TreeNode;

use Shudd3r\Filesystem\Virtual\Root\TreeNode;
use Shudd3r\Filesystem\Node;
use Generator;


class Directory extends TreeNode
{
    private array $nodes;
    private int   $access;

    /**
     * @param array<string, TreeNode> $nodes
     */
    public function __construct(array $nodes = [], int $access = null)
    {
        $this->nodes  = $nodes;
        $this->access = $access ?? Node::READ | Node::WRITE;
    }

    public function add(string $name, TreeNode $node): void
    {
        $this->nodes[$name] = $node;
    }

    public function unlink(string $name): void
    {
        unset($this->nodes[$name]);
    }

    public function node(string ...$pathSegments): TreeNode
    {
        if (!$pathSegments) { return $this; }
        $child = array_shift($pathSegments);
        $node  = $this->nodes[$child] ?? new MissingNode($this, $child);
        return $pathSegments ? $node->node(...$pathSegments) : $node;
    }

    public function isDir(): bool
    {
        return true;
    }

    public function filenames(string $path = ''): Generator
    {
        $nodes = $this->nodes;
        ksort($nodes, SORT_STRING);
        foreach ($nodes as $name => $node) {
            if ($node->isLink()) { continue; }
            $pathname = $path ? $path . '/' . $name : $name;
            $node->isDir() ? yield from $node->filenames($pathname) : yield $pathname;
        }
    }

    public function isAllowed(int $access): bool
    {
        return ($access & $this->access) === $access;
    }
}
