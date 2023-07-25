<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Local;

use Shudd3r\Filesystem\Node;
use Shudd3r\Filesystem\Exception;


abstract class LocalNode implements Node
{
    protected Pathname $pathname;
    protected bool     $isFile = true;

    /**
     * Node represented by this instance doesn't need to exist within local
     * filesystem unless it's a root directory instantiated with a Pathname
     * without relative name.
     */
    public function __construct(Pathname $pathname)
    {
        $this->pathname = $pathname;
    }

    public function pathname(): string
    {
        return $this->pathname->absolute();
    }

    public function name(): string
    {
        return $this->pathname->relative();
    }

    abstract public function exists(): bool;

    public function isReadable(): bool
    {
        if ($this->exists()) { return is_readable($this->pathname()); }
        if (file_exists($this->pathname())) { return false; }
        $ancestor = $this->pathname->closestAncestor();
        return is_dir($ancestor) && is_readable($ancestor);
    }

    public function isWritable(): bool
    {
        if ($this->exists()) { return is_writable($this->pathname()); }
        if (file_exists($this->pathname())) { return false; }
        $ancestor = $this->pathname->closestAncestor();
        return is_dir($ancestor) && is_writable($ancestor);
    }

    public function isRemovable(): bool
    {
        if (!$this->name()) { return false; }
        $path   = $this->pathname();
        $exists = $this->exists();

        $nodeTypeMismatch = !$exists && file_exists($path);
        if ($nodeTypeMismatch) { return false; }

        $existingNodeAccess = !$exists || is_writable($path) && is_readable($path);
        if (!$existingNodeAccess) { return false; }

        $ancestor = $this->pathname->closestAncestor();
        return is_dir($ancestor) && is_writable($ancestor);
    }

    public function validated(int $flags = 0): self
    {
        $path = $this->validPath();
        if ($flags & self::READ && !is_readable($path)) {
            throw Exception\FailedPermissionCheck::forRead($this->pathname->relative(), $path, $this->isFile);
        }

        if ($flags & self::WRITE && !is_writable($path)) {
            throw Exception\FailedPermissionCheck::forWrite($this->pathname->relative(), $path, $this->isFile);
        }

        if ($flags & self::REMOVE) { $this->verifyRemove(); }

        return $this;
    }

    public function remove(): void
    {
        if (!$this->exists()) { return; }
        $this->validated(self::REMOVE)->removeNode();
    }

    abstract protected function removeNode(): void;

    private function validPath(): string
    {
        $path = $this->pathname->absolute();
        if ($this->exists()) { return $path; }

        if (file_exists($path) || is_link($path)) {
            throw Exception\UnexpectedNodeType::for($this->pathname->relative(), $path, $this->isFile);
        }

        $ancestorPath = $this->pathname->closestAncestor();
        if (!is_dir($ancestorPath)) {
            throw Exception\UnexpectedLeafNode::for($this->pathname->relative(), $ancestorPath);
        }

        return $ancestorPath;
    }

    private function verifyRemove(): void
    {
        if (!$this->pathname->relative()) {
            throw Exception\FailedPermissionCheck::forRootRemove($this->pathname->absolute());
        }

        $path = $this->pathname->closestAncestor();
        if (!is_writable($path)) {
            throw Exception\FailedPermissionCheck::forRemove($this->pathname->relative(), $path, $this->isFile);
        }
    }
}
