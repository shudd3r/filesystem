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

use Shudd3r\Filesystem\Node as FilesystemNode;
use Shudd3r\Filesystem\Generic\Pathname;
use Shudd3r\Filesystem\Virtual\Root\Node;
use Shudd3r\Filesystem\Exception;


abstract class VirtualNode implements FilesystemNode
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
        return $this->isAllowed(self::READ);
    }

    public function isWritable(): bool
    {
        return $this->isAllowed(self::WRITE);
    }

    public function isRemovable(): bool
    {
        return $this->isAllowed(self::REMOVE);
    }

    public function validated(int $flags = self::PATH): self
    {
        $node = $this->node();
        $this->verifyNodeType($node, $flags);
        $this->verifyPath($node);
        $this->verifyAccess($node, $flags);

        return $this;
    }

    public function remove(): void
    {
        if (!$this->exists()) { return; }
        $this->validated(self::REMOVE)->node()->remove();
    }

    abstract protected function nodeExists(Node $node): bool;

    protected function node(): Node
    {
        return $this->root->node($this->pathname());
    }

    private function isAllowed(int $access): bool
    {
        $node  = $this->node();
        $valid = $node->isValid() && (!$node->exists() || $this->nodeExists($node));
        return $valid && $node->isAllowed($access);
    }

    private function verifyNodeType(Node $node, int $flags): void
    {
        if ($this->nodeExists($node)) { return; }
        if ($node->exists() || $node->isLink()) {
            throw Exception\UnexpectedNodeType::forNode($this);
        }
        if ($flags & self::EXISTS) {
            throw Exception\NodeNotFound::forNode($this);
        }
    }

    private function verifyPath(Node $node): void
    {
        if ($node->isValid() || $node->isLink()) { return; }
        throw Exception\UnexpectedLeafNode::forNode($this, $node->foundPath());
    }

    private function verifyAccess(Node $node, int $flags): void
    {
        if ($flags & self::READ && !$node->isAllowed(self::READ)) {
            throw Exception\PermissionDenied::forNodeRead($this);
        }

        if ($flags & self::WRITE && !$node->isAllowed(self::WRITE)) {
            throw Exception\PermissionDenied::forNodeWrite($this);
        }

        if ($flags & self::REMOVE && !$node->isAllowed(self::REMOVE)) {
            throw $this->removeDeniedException($node);
        }
    }

    private function removeDeniedException(Node $node): Exception\PermissionDenied
    {
        if (!$this->name()) {
            return Exception\PermissionDenied::forRootRemove($this);
        }

        return $node->isAllowed(self::READ | self::WRITE)
            ? Exception\PermissionDenied::forNodeRemove($this, dirname($this->pathname()))
            : Exception\PermissionDenied::forNodeRemove($this);
    }
}
