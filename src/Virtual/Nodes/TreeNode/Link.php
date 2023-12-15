<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Virtual\Nodes\TreeNode;

use Shudd3r\Filesystem\Virtual\Nodes\TreeNode;


class Link extends TreeNode
{
    private string $targetPath;
    private array  $missingSegments = [];

    public function __construct(string $targetPath)
    {
        $this->targetPath = $targetPath;
    }

    public function node(string ...$pathSegments): TreeNode
    {
        $clone = clone $this;
        $clone->missingSegments = array_merge($this->missingSegments, $pathSegments);
        return $clone;
    }

    public function exists(): bool
    {
        return empty($this->missingSegments);
    }

    public function isLink(): bool
    {
        return empty($this->missingSegments);
    }

    public function target(): string
    {
        return $this->targetPath;
    }

    public function setTarget(string $path): void
    {
        $this->targetPath = $path;
    }

    public function missingSegments(): array
    {
        return $this->missingSegments;
    }
}
