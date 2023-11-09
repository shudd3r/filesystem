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

use Shudd3r\Filesystem\File;
use Shudd3r\Filesystem\Directory;
use Shudd3r\Filesystem\Exception\IOException;
use Shudd3r\Filesystem\Generic\ContentStream;


class LocalFile extends LocalNode implements File
{
    public function exists(): bool
    {
        return is_file($this->pathname->absolute());
    }

    public function contents(): string
    {
        if (!$this->validated(self::READ)->exists()) { return ''; }

        $file     = @fopen($this->pathname->absolute(), 'rb');
        $lock     = $file && @flock($file, LOCK_SH);
        $contents = $lock ? @file_get_contents($this->pathname->absolute()) : false;
        $lock && flock($file, LOCK_UN);
        $file && fclose($file);

        if ($contents !== false) { return $contents; }
        throw IOException\UnableToReadContents::fromFile($this);
    }

    public function write(string $contents): void
    {
        $this->save($contents, LOCK_EX);
    }

    public function writeStream(ContentStream $stream): void
    {
        if ($this->samePath($stream->uri())) { return; }
        $this->save($stream->resource(), LOCK_EX);
    }

    public function append(string $contents): void
    {
        $this->save($contents, FILE_APPEND);
    }

    public function copy(File $file): void
    {
        if ($this->selfReference($file)) { return; }

        $stream = $file->contentStream();
        $this->save($stream ? $stream->resource() : $file->contents(), LOCK_EX);
    }

    public function moveTo(Directory $directory, string $name = null): void
    {
        if (!$this->validated(self::READ | self::REMOVE)->exists()) { return; }

        $file = $directory->file($name ?? basename($this->pathname()));
        if (!$file instanceof self) {
            $file->copy($this);
            $this->remove();
            return;
        }

        $from = $this->pathname();
        $to   = $file->validated(self::WRITE)->pathname();

        $fileToLink = $file->exists() && is_link($from) && !is_link($to);
        if ($fileToLink && $this->samePath($to)) {
            $this->remove();
            return;
        }

        if (!$file->exists()) { $file->createDirectory(); }
        rename($from, $to);
    }

    public function contentStream(): ?ContentStream
    {
        if (!$this->exists() || !$this->isReadable()) { return null; }
        $resource = @fopen($this->pathname->absolute(), 'rb');
        return $resource ? new ContentStream($resource) : null;
    }

    protected function removeNode(): void
    {
        if (@unlink($this->pathname->absolute())) { return; }
        throw IOException\UnableToRemove::node($this);
    }

    private function save($contents, int $flags): void
    {
        $exists = $this->validated(self::WRITE)->exists();
        $exists ? $this->putContents($contents, $flags) : $this->create($contents, $flags);
    }

    private function create($contents, int $flags): void
    {
        $this->createDirectory();
        $this->putContents($contents, $flags, true);
        if (@chmod($this->pathname->absolute(), 0644)) { return; }
        throw IOException\UnableToSetPermissions::forNode($this);
    }

    private function putContents($contents, int $flags, bool $create = false): void
    {
        $written = @file_put_contents($this->pathname->absolute(), $contents, $flags);
        if ($written !== false) { return; }
        throw $create ? IOException\UnableToCreate::node($this) : IOException\UnableToWriteContents::toFile($this);
    }

    private function createDirectory(): void
    {
        $directoryPath = dirname($this->pathname->absolute());
        if (is_dir($directoryPath)) { return; }
        if (@mkdir($directoryPath, 0755, true)) { return; }
        throw IOException\UnableToCreate::directories($this);
    }

    private function selfReference(File $file): bool
    {
        return $file instanceof self && $this->samePath($file->pathname());
    }

    private function samePath(string $pathname): bool
    {
        return is_file($pathname) && realpath($pathname) === realpath($this->pathname->absolute());
    }
}
