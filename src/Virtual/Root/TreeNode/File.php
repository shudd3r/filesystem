<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Virtual\Root\TreeNode;

use Shudd3r\Filesystem\Virtual\Root\TreeNode;
use Shudd3r\Filesystem\Node;


class File extends TreeNode
{
    private string $contents;
    private int    $access;

    public function __construct(string $contents = '', int $access = null)
    {
        $this->contents = $contents;
        $this->access   = $access ?? Node::READ | Node::WRITE;
    }

    public function node(string ...$pathSegments): TreeNode
    {
        return new InvalidNode(...$pathSegments);
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

    public function isAllowed(int $access): bool
    {
        return ($access & $this->access) === $access;
    }
}
