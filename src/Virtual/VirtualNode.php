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

use Shudd3r\Filesystem\Node;
use Shudd3r\Filesystem\Exception;


abstract class VirtualNode implements Node
{
    protected NodeData $nodes;
    protected string   $root;
    protected string   $name;

    /**
     * @param NodeData $nodes Root instance of NodeData
     * @param string   $root  Path to root node (without prefix)
     * @param string   $name  Node name
     */
    public function __construct(NodeData $nodes, string $root = '', string $name = '')
    {
        $this->nodes = $nodes;
        $this->root  = $root ?: 'vfs:/';
        $this->name  = $name;
    }

    public function pathname(): string
    {
        return $this->rootPath();
    }

    public function name(): string
    {
        return $this->name;
    }

    public function exists(): bool
    {
        return $this->nodeExists($this->nodeData());
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function isWritable(): bool
    {
        return true;
    }

    public function isRemovable(): bool
    {
        return true;
    }

    public function validated(int $flags = self::PATH): self
    {
        $node = $this->nodeData();
        if ($this->nodeExists($node)) { return $this; }
        if ($flags & self::EXISTS) {
            throw Exception\NodeNotFound::forNode($this);
        }

        if ($node->exists() || $node->isLink()) {
            throw Exception\UnexpectedNodeType::forNode($this);
        }

        if (!$node->isValid()) {
            $collision = substr($this->pathname(), 0, -strlen('/' . $node->missingPath()));
            throw Exception\UnexpectedLeafNode::forNode($this, $collision);
        }

        return $this;
    }

    public function remove(): void
    {
        if (!$this->exists()) { return; }
        $this->validated(self::REMOVE)->nodeData()->remove();
    }

    abstract protected function nodeExists(NodeData $node): bool;

    protected function nodeData(): NodeData
    {
        return $this->nodes->nodeData($this->rootPath());
    }

    protected function rootPath(): string
    {
        if ($this->name) {
            return $this->root . '/' . $this->name;
        }
        return str_ends_with($this->root, '/') ? $this->root . '/' : $this->root;
    }
}
