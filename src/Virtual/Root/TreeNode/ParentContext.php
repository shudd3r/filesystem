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


class ParentContext extends TreeNode
{
    private TreeNode  $node;
    private Directory $parent;
    private string    $name;

    public function __construct(TreeNode $node, Directory $parent, string $name)
    {
        $this->node   = $node;
        $this->parent = $parent;
        $this->name   = $name;
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

    public function filenames(): Generator
    {
        return $this->node->filenames();
    }

    public function remove(): void
    {
        $this->parent->unlink($this->name);
        $this->node = new MissingNode($this->parent, $this->name);
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
        return $this->node->target();
    }

    public function setTarget(string $path): void
    {
        $this->node->setTarget($path);
    }

    public function moveTo(TreeNode $target): void
    {
        if ($target->setNode($this->node)) { $this->remove(); }
    }

    protected function setNode(TreeNode $node): bool
    {
        if (!$node = $this->movableNode($node)) { return false; }
        $this->parent->add($this->name, $node);
        return true;
    }

    private function movableNode(TreeNode $node): ?TreeNode
    {
        if ($node === $this->node) { return null; }
        return $node instanceof LinkedNode ? $node->originalNode($this->node) : $node;
    }
}
