<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Virtual\TreeNode;

use Shudd3r\Filesystem\Virtual\TreeNode;
use Generator;


class Directory extends TreeNode
{
    private array $nodes;

    /**
     * @param array<string, TreeNode> $nodes
     */
    public function __construct(array $nodes = [])
    {
        $this->nodes = $nodes;
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
        $node  = $this->nodes[$child] ?? new MissingNode($child);
        return $pathSegments ? $node->node(...$pathSegments) : $node;
    }

    public function isDir(): bool
    {
        return true;
    }

    public function filenames(): Generator
    {
        yield from [];
    }
}
