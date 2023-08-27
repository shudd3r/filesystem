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


class VirtualFile extends VirtualNode implements File
{
    public function contents(): string
    {
        return $this->validated(self::READ)->nodeData()->contents();
    }

    public function write(string $contents): void
    {
        $this->validated(self::WRITE)->nodeData()->putContents($contents);
    }

    public function writeStream(ContentStream $stream): void
    {
        $this->validated(self::WRITE)->nodeData()->putContents(stream_get_contents($stream->resource()));
    }

    public function append(string $contents): void
    {
        $node = $this->validated(self::WRITE)->nodeData();
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
        $node = $this->nodeData();
        if (!$this->nodeExists($node)) { return; }
        $file = $directory->file($name ?? basename($this->name));
        if ($this->selfReference($file)) { return; }
        $file->copy($this);
        $this->remove();
    }

    public function contentStream(): ?ContentStream
    {
        return null;
    }

    protected function nodeExists(NodeData $node): bool
    {
        return $node->isFile();
    }

    private function selfReference(File $file): bool
    {
        return $file instanceof self && $this->pathname() === $file->pathname();
    }
}
