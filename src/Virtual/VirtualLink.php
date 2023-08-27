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
use Shudd3r\Filesystem\Exception;


class VirtualLink extends VirtualNode implements Link
{
    public function target(bool $showRemoved = false): ?string
    {
        $node = $this->validated()->nodeData();
        $show = $this->nodeExists($node) && ($showRemoved || $node->exists());
        return $show ? NodeData::ROOT . $node->target() : null;
    }

    public function setTarget(Node $node): void
    {
        $this->nodeData()->setTarget($this->targetPath($node));
    }

    public function isDirectory(): bool
    {
        return $this->nodeData()->isDir();
    }

    public function isFile(): bool
    {
        return $this->nodeData()->isFile();
    }

    protected function nodeExists(NodeData $node): bool
    {
        return $node->isLink();
    }

    private function targetPath(Node $node): string
    {
        if (!$node instanceof VirtualNode) {
            throw Exception\IOException\UnableToCreate::externalLink($this);
        }

        if ($node instanceof Link) {
            throw Exception\IOException\UnableToCreate::indirectLink($this);
        }

        $data     = $node->validated(self::EXISTS)->nodeData();
        $mismatch = $this->isDirectory() && !$data->isDir() || $this->isFile() && !$data->isFile();
        if ($mismatch) {
            throw Exception\UnexpectedNodeType::forLink($this, $node);
        }

        return $node->rootPath();
    }
}
