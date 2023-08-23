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


abstract class VirtualNode
{
    public const PATH   = 0;
    public const EXISTS = 8;

    protected NodeTree $nodes;
    protected string   $root;
    protected string   $name;

    public function __construct(NodeTree $nodes, string $root, string $name)
    {
        $this->nodes = $nodes;
        $this->root  = $root;
        $this->name  = $name;
    }

    public function pathname(): string
    {
        return $this->root . '/' . $this->name;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function exists(): bool
    {
        return $this->nodes->exists($this);
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
        $this->nodes->validate($this, $flags);
        return $this;
    }

    public function remove(): void
    {
        $this->nodes->remove($this);
    }
}
