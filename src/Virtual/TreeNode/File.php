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


class File extends TreeNode
{
    private string $contents;

    public function __construct(string $contents = '')
    {
        $this->contents = $contents;
    }

    public function node(string $path): TreeNode
    {
        return new InvalidNode($path);
    }

    public function isFile(): bool
    {
        return true;
    }

    public function contents(): string
    {
        return $this->contents;
    }

    public function putContents(string $contents): void
    {
        $this->contents = $contents;
    }
}
