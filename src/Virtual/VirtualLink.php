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

use Shudd3r\Filesystem\Link;
use Shudd3r\Filesystem\Node;
use Shudd3r\Filesystem\Virtual\Root\TreeNode;
use Shudd3r\Filesystem\Exception;


class VirtualLink extends VirtualNode implements Link
{
    public function target(bool $showRemoved = false): ?string
    {
        $node = $this->validated()->node();
        $show = $this->nodeExists($node) && ($showRemoved || $node->exists());
        return $show ? $node->target() : null;
    }

    public function setTarget(Node $node): void
    {
        $this->node()->setTarget($this->targetPath($node));
    }

    public function isDirectory(): bool
    {
        return $this->node()->isDir();
    }

    public function isFile(): bool
    {
        return $this->node()->isFile();
    }

    protected function nodeExists(TreeNode $node): bool
    {
        return $node->isLink();
    }

    private function targetPath(Node $target): string
    {
        if (!$target instanceof VirtualNode || $target->root !== $this->root) {
            throw Exception\IOException\UnableToCreate::externalLink($this);
        }

        if ($target instanceof Link) {
            throw Exception\IOException\UnableToCreate::indirectLink($this);
        }

        $node     = $target->validated(self::EXISTS)->node();
        $mismatch = $this->isDirectory() && !$node->isDir() || $this->isFile() && !$node->isFile();
        if ($mismatch) {
            throw Exception\UnexpectedNodeType::forLink($this, $target);
        }

        return $target->pathname();
    }
}
