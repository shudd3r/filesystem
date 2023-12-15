<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Virtual\Nodes;

use LogicException;
use Generator;


abstract class TreeNode
{
    public function node(string ...$pathSegments): self
    {
        throw new LogicException();
    }

    public function equals(TreeNode $node): bool
    {
        return $this === $node;
    }

    public function foundPath(): string
    {
        return '';
    }

    public function realPath(): ?string
    {
        return '';
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

    public function remove(): void
    {
        throw new LogicException();
    }

    public function createDir(): void
    {
        throw new LogicException();
    }

    public function filenames(): Generator
    {
        yield from [];
    }

    public function contents(): string
    {
        return '';
    }

    public function putContents(string $contents): void
    {
        throw new LogicException();
    }

    public function target(): ?string
    {
        return null;
    }

    public function setTarget(string $path): void
    {
        throw new LogicException();
    }

    public function missingSegments(): array
    {
        return [];
    }

    public function moveTo(TreeNode $target): void
    {
        throw new LogicException();
    }

    public function isAllowed(int $access): bool
    {
        return true;
    }

    protected function attachNode(TreeNode $node): void
    {
        throw new LogicException();
    }

    protected function baseNode(TreeNode $overwrite = null): ?TreeNode
    {
        return $this === $overwrite ? null : $this;
    }
}
