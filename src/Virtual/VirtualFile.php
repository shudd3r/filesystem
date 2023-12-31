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

use Shudd3r\Filesystem\File;
use Shudd3r\Filesystem\Directory;
use Shudd3r\Filesystem\Generic\ContentStream;
use Shudd3r\Filesystem\Virtual\Nodes\Node;


class VirtualFile extends VirtualNode implements File
{
    public function contents(): string
    {
        return $this->validated(self::READ)->node()->contents();
    }

    public function write(string $contents): void
    {
        $this->validated(self::WRITE)->node()->putContents($contents);
    }

    public function writeStream(ContentStream $stream): void
    {
        $this->validated(self::WRITE)->node()->putContents($stream->contents());
    }

    public function append(string $contents): void
    {
        $node = $this->validated(self::WRITE)->node();
        $node->putContents($node->contents() . $contents);
    }

    public function copy(File $file): void
    {
        if ($this->selfReference($file)) { return; }
        $stream = $file->contentStream();
        $stream ? $this->writeStream($stream) : $this->write($file->contents());
    }

    public function moveTo(Directory $directory, string $name = null): void
    {
        $node = $this->validated(self::READ | self::REMOVE)->node();
        if (!$this->nodeExists($node)) { return; }

        $file = $directory->file($name ?? basename($this->pathname()));
        $this->sameRoot($file) ? $this->moveNode($node, $file) : $this->moveToFilesystem($node, $file);
    }

    public function contentStream(): ?ContentStream
    {
        return null;
    }

    protected function nodeExists(Node $node): bool
    {
        return $node->isFile();
    }

    private function selfReference(File $file): bool
    {
        return $this->sameRoot($file) && $this->node()->equals($this->nodes->node($file->pathname()));
    }

    private function sameRoot(File $file): bool
    {
        return $file instanceof self && $this->nodes === $file->nodes;
    }

    private function moveNode(Node $node, File $file): void
    {
        $targetNode = $this->nodes->node($file->validated(self::WRITE)->pathname());
        $node->moveTo($targetNode);
    }

    private function moveToFilesystem(Node $node, File $file): void
    {
        $file->copy($this);
        $node->remove();
    }
}
