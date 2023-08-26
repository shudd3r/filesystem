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

use Shudd3r\Filesystem\Exception;


abstract class VirtualNode
{
    public const PATH   = 0;
    public const EXISTS = 8;

    protected NodeData $nodes;
    protected string   $root;
    protected string   $name;

    public function __construct(NodeData $nodes, string $root = '', string $name = '')
    {
        $this->nodes = $nodes;
        $this->root  = $root;
        $this->name  = $name;
    }

    public function pathname(): string
    {
        return NodeData::ROOT . $this->rootPath();
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
            throw new Exception\NodeNotFound();
        }

        if ($node->exists() || $node->isLink()) {
            throw new Exception\UnexpectedNodeType();
        }

        if (!$node->isValid()) {
            throw new Exception\UnexpectedLeafNode();
        }

        return $this;
    }

    public function remove(): void
    {
        $node = $this->nodeData();
        if (!$this->nodeExists($node)) { return; }
        $node->remove();
    }

    abstract protected function nodeExists(NodeData $node): bool;

    protected function nodeData(): NodeData
    {
        return $this->nodes->nodeData($this->rootPath());
    }

    protected function rootPath(): string
    {
        if (!$this->root) { return $this->name; }
        return $this->name ? $this->root . '/' . $this->name : $this->root;
    }
}
