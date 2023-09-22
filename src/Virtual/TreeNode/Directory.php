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

    public function node(string $path): TreeNode
    {
        if (!$path) { return $this; }

        [$child, $subPath] = $this->splitPath($path);

        $node = $this->nodes[$child] ?? new MissingNode($path);
        return $subPath ? $node->node($subPath) : $node;
    }

    public function isDir(): bool
    {
        return true;
    }

    public function filenames(): Generator
    {
        yield from [];
    }

    public function add(string $path, TreeNode $node): void
    {
        [$child, $subPath] = $this->splitPath($path);
        if (!$child || isset($this->nodes[$child])) {
            parent::add($path, $node);
        }
        if ($subPath) {
            $subNode = new self([]);
            $subNode->add($subPath, $node);
        }

        $this->nodes[$child] = $subNode ?? $node;
    }

    public function remove(string $path): void
    {
        if (!$path) { parent::remove($path); }
        [$child, $subPath] = $this->splitPath($path);

        $node = $this->nodes[$child] ?? null;
        if (!$node) { return; }
        if ($subPath) {
            $node->remove($subPath);
            return;
        }

        unset($this->nodes[$path]);
    }

    private function splitPath(string $path): array
    {
        return explode('/', $path, 2) + [null, null];
    }
}
