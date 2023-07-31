<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Local;

use Shudd3r\Filesystem\Node;
use Shudd3r\Filesystem\Exception\NodeNotFound;
use Shudd3r\Filesystem\Exception\UnexpectedNodeType;
use Shudd3r\Filesystem\Exception\IOException;


class LocalLink extends LocalNode
{
    public function exists(): bool
    {
        return is_link($this->pathname->absolute());
    }

    public function target(bool $includeRemoved = false): ?string
    {
        $path = $this->pathname->absolute();
        $show = $includeRemoved || is_file($path) || is_dir($path);
        return $show ? readlink($path) : null;
    }

    public function setTarget(Node $node): void
    {
        $path = $node->validated(self::EXISTS)->pathname();
        if (!is_dir($path) && !is_file($path)) {
            throw NodeNotFound::forNode($node);
        }

        $mismatch = $this->isDirectory() && !is_dir($path) || $this->isFile() && !is_file($path);
        if ($mismatch) {
            throw UnexpectedNodeType::forLink($this, $node);
        }

        $windowsOverwrite = DIRECTORY_SEPARATOR === '\\' && $this->exists();
        if ($windowsOverwrite) {
            // @codeCoverageIgnoreStart
            !$this->validated(self::WRITE)->isFile() && $this->remove();
            // @codeCoverageIgnoreEnd
        }

        $this->exists() ? $this->changeTargetPath($node) : $this->createLink($node, $this->pathname->absolute());
    }

    public function isDirectory(): bool
    {
        return is_dir($this->pathname->absolute());
    }

    public function isFile(): bool
    {
        return is_file($this->pathname->absolute());
    }

    protected function removeNode(): void
    {
        $path    = $this->pathname->absolute();
        $isWinOS = DIRECTORY_SEPARATOR === '\\';
        $removed = $isWinOS ? @unlink($path) || @rmdir($path) : @unlink($path);

        if (!$removed) { throw IOException\UnableToRemove::node($this); }
    }

    private function changeTargetPath(Node $target): void
    {
        $link = $this->pathname->absolute();
        $temp = $link . '~';
        $this->createLink($target, $temp);
        if (@rename($temp, $link)) { return; }
        @unlink($temp) || @rmdir($temp);
        throw IOException\UnableToCreate::symlink($this, $target);
    }

    private function createLink(Node $target, string $link): void
    {
        if (@symlink($target->pathname(), $link)) { return; }
        throw IOException\UnableToCreate::symlink($this, $target);
    }
}
