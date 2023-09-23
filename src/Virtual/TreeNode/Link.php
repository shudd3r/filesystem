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


class Link extends TreeNode
{
    private string $targetPath;
    private string $missingPath = '';

    public function __construct(string $targetPath)
    {
        $this->targetPath = $targetPath;
    }

    public function node(string $path): TreeNode
    {
        $clone = clone $this;
        $clone->missingPath = $path;
        return $clone;
    }

    public function isLink(): bool
    {
        return true;
    }

    public function missingPath(): string
    {
        return $this->missingPath;
    }

    public function target(): string
    {
        return $this->targetPath;
    }

    public function setTarget(string $path): void
    {
        $this->targetPath = $path;
    }
}
