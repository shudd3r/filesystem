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
use Generator;


class LinkedNode extends TreeNode
{
    private Link     $link;
    private TreeNode $node;

    public function __construct(Link $link, TreeNode $node)
    {
        $this->link = $link;
        $this->node = $node;
    }

    public function equals(TreeNode $node): bool
    {
        return $this->node->equals($node);
    }

    public function exists(): bool
    {
        return $this->node->exists();
    }

    public function isFile(): bool
    {
        return $this->node->isFile();
    }

    public function isDir(): bool
    {
        return $this->node->isDir();
    }

    public function isLink(): bool
    {
        return true;
    }

    public function target(): string
    {
        return $this->link->target();
    }

    public function setTarget(string $path): void
    {
        $this->link->setTarget($path);
    }

    public function contents(): string
    {
        return $this->node->contents();
    }

    public function putContents(string $contents): void
    {
        $this->node->putContents($contents);
    }

    public function filenames(): Generator
    {
        return $this->node->filenames();
    }

    public function missingSegments(): array
    {
        return $this->node->missingSegments();
    }

    public function isAllowed(int $access): bool
    {
        return $this->node->isAllowed($access);
    }

    protected function baseNode(TreeNode $overwrite = null): ?TreeNode
    {
        if (!$overwrite) { return $this->link; }
        return $overwrite === $this->node ? $this->node : $this->link->baseNode($overwrite);
    }
}
