<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Virtual\Root;

use Shudd3r\Filesystem\Generic\Pathname;
use Generator;


class Node
{
    private Pathname $root;
    private TreeNode $node;
    private string   $foundPath;
    private string   $realPath;

    public function __construct(Pathname $root, TreeNode $node, string $foundPath, string $realPath = null)
    {
        $this->root      = $root;
        $this->node      = $node;
        $this->foundPath = $foundPath;
        $this->realPath  = $realPath ?? $foundPath;
    }

    public function equals(Node $node): bool
    {
        return $this->node->equals($node->node);
    }

    public function foundPath(): string
    {
        $segments = $this->node->missingSegments();
        $notFound = $segments ? '/' . implode('/', $segments) : '';
        if (!$notFound) { return $this->foundPath; }
        $foundPath = substr($this->foundPath, 0, -strlen($notFound));
        return str_ends_with($foundPath, '/') ? $foundPath . '/' : $foundPath;
    }

    public function realPath(): ?string
    {
        return $this->isValid() ? $this->realPath : null;
    }

    public function exists(): bool
    {
        return $this->node->exists();
    }

    public function isDir(): bool
    {
        return $this->node->isDir();
    }

    public function isFile(): bool
    {
        return $this->node->isFile();
    }

    public function isLink(): bool
    {
        return $this->node->isLink();
    }

    public function isValid(): bool
    {
        return $this->node->isValid();
    }

    public function remove(): void
    {
        $this->node->remove();
    }

    public function createDir(): void
    {
        $this->node->createDir();
    }

    public function filenames(): Generator
    {
        return $this->node->filenames();
    }

    public function contents(): string
    {
        return $this->node->contents();
    }

    public function putContents(string $contents): void
    {
        $this->node->putContents($contents);
    }

    public function target(): string
    {
        $target = $this->node->target();
        return $target ? $this->root->forChildNode($target)->absolute() : $this->root->absolute();
    }

    public function setTarget(string $path): void
    {
        $pathname = $this->root->asRootFor($path);
        $this->node->setTarget(str_replace($this->root->separator(), '/', $pathname->relative()));
    }

    public function moveTo(Node $target): void
    {
        $this->node->moveTo($target->node);
    }

    public function isAllowed(int $access): bool
    {
        return $this->node->isAllowed($access);
    }
}
