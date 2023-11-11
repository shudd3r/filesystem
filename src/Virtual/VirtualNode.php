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
use Shudd3r\Filesystem\Generic\Pathname;
use Shudd3r\Filesystem\Virtual\Root\TreeNode;
use Shudd3r\Filesystem\Exception;


abstract class VirtualNode implements Node
{
    protected Root     $root;
    protected Pathname $path;

    protected function __construct(Root $root, Pathname $path)
    {
        $this->root = $root;
        $this->path = $path;
    }

    public function pathname(): string
    {
        return $this->path->absolute();
    }

    public function name(): string
    {
        return $this->path->relative();
    }

    public function exists(): bool
    {
        return $this->nodeExists($this->node());
    }

    public function isReadable(): bool
    {
        return $this->node()->isValid();
    }

    public function isWritable(): bool
    {
        return $this->node()->isValid();
    }

    public function isRemovable(): bool
    {
        return $this->node()->isValid();
    }

    public function validated(int $flags = self::PATH): self
    {
        $node = $this->node();
        if ($this->nodeExists($node)) { return $this; }
        if ($flags & self::EXISTS) {
            throw Exception\NodeNotFound::forNode($this);
        }

        if ($node->exists() || $node->isLink()) {
            throw Exception\UnexpectedNodeType::forNode($this);
        }

        if (!$node->isValid()) {
            $collision = substr($this->pathname(), 0, -strlen('/' . implode('/', $node->missingSegments())));
            throw Exception\UnexpectedLeafNode::forNode($this, $collision);
        }

        return $this;
    }

    public function remove(): void
    {
        if (!$this->exists()) { return; }
        $this->validated(self::REMOVE)->node()->remove();
    }

    abstract protected function nodeExists(TreeNode $node): bool;

    protected function node(): TreeNode
    {
        return $this->root->node($this->pathname());
    }
}
