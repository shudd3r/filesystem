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

use Shudd3r\Filesystem\Exception\UnsupportedOperation;
use Generator;


abstract class TreeNode
{
    public function node(string $path): self
    {
        throw new UnsupportedOperation();
    }

    public function exists(): bool
    {
        return true;
    }

    public function isDir(): bool
    {
        return false;
    }

    public function isFile(): bool
    {
        return false;
    }

    public function isLink(): bool
    {
        return false;
    }

    public function isValid(): bool
    {
        return true;
    }

    public function filenames(): Generator
    {
        throw new UnsupportedOperation();
    }

    public function add(string $path, TreeNode $node): void
    {
        throw new UnsupportedOperation();
    }

    public function remove(string $path): void
    {
        throw new UnsupportedOperation();
    }

    public function contents(): string
    {
        throw new UnsupportedOperation();
    }

    public function putContents(string $contents): void
    {
        throw new UnsupportedOperation();
    }

    public function target(): ?string
    {
        throw new UnsupportedOperation();
    }

    public function setTarget(string $path): void
    {
        throw new UnsupportedOperation();
    }

    public function missingPath(): string
    {
        return '';
    }
}
