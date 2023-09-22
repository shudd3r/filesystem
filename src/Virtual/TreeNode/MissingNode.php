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


class MissingNode extends TreeNode
{
    private string $missingPath;

    public function __construct(string $missingPath)
    {
        $this->missingPath = $missingPath;
    }

    public function node(string $path): TreeNode
    {
        return $this;
    }

    public function exists(): bool
    {
        return false;
    }

    public function missingPath(): string
    {
        return $this->missingPath;
    }
}
