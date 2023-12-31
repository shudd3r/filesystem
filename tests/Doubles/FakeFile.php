<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Tests\Doubles;

use Shudd3r\Filesystem\File;
use Shudd3r\Filesystem\Directory;
use Shudd3r\Filesystem\Generic\ContentStream;


class FakeFile implements File
{
    private string  $name;
    private ?string $contents;

    public function __construct(string $name, ?string $contents = null)
    {
        $this->name     = $name;
        $this->contents = $contents;
    }

    public function pathname(): string
    {
        return 'virtual://' . $this->name;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function exists(): bool
    {
        return isset($this->contents);
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function isWritable(): bool
    {
        return true;
    }

    public function isRemovable(): bool
    {
        return true;
    }

    public function validated(int $flags = 0): self
    {
        return $this;
    }

    public function remove(): void
    {
        $this->contents = null;
    }

    public function contents(): string
    {
        return $this->contents ?? '';
    }

    public function write(string $contents): void
    {
        $this->contents = $contents;
    }

    public function writeStream(ContentStream $stream): void
    {
        $this->contents = 'stream contents';
    }

    public function append(string $contents): void
    {
        $this->contents = $this->contents() . $contents;
    }

    public function copy(File $file): void
    {
        $this->contents = $file->contents();
    }

    public function moveTo(Directory $directory, string $name = null): void
    {
        if (!$this->exists()) { return; }
        $directory->file($name ?? basename($this->name))->copy($this);
        $this->remove();
    }

    public function contentStream(): ?ContentStream
    {
        return null;
    }
}
