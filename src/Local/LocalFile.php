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


class LocalFile implements File
{
    use PathValidation;

    private Pathname $pathname;
    private string   $filename;

    public function __construct(Pathname $pathname)
    {
        $this->pathname = $pathname;
        $this->filename = $pathname->absolute();
    }

    public function pathname(): string
    {
        return $this->filename;
    }

    public function name(): string
    {
        return $this->pathname->relative();
    }

    public function exists(): bool
    {
        return is_file($this->filename);
    }

    public function validated(int $flags = 0): self
    {
        $this->verifyPath($this->pathname, $flags, true);
        return $this;
    }

    public function isReadable(): bool
    {
        if ($this->exists()) { return is_readable($this->filename); }
        $ancestor = $this->pathname->closestAncestor();
        return is_dir($ancestor) && is_readable($ancestor);
    }

    public function isWritable(): bool
    {
        if ($this->exists()) { return is_writable($this->filename); }
        $ancestor = $this->pathname->closestAncestor();
        return is_dir($ancestor) && is_writable($ancestor);
    }

    public function remove(): void
    {
        $this->exists() && unlink($this->filename);
    }

    public function contents(): string
    {
        if (!$this->validated(self::READ)->exists()) { return ''; }

        $file = fopen($this->filename, 'rb');
        flock($file, LOCK_SH);
        $contents = file_get_contents($this->filename);
        flock($file, LOCK_UN);
        fclose($file);

        return $contents;
    }

    public function write(string $contents): void
    {
        $this->validated(self::WRITE)->save($contents, LOCK_EX);
    }

    public function append(string $contents): void
    {
        $this->validated(self::WRITE)->save($contents, FILE_APPEND);
    }

    private function save(string $contents, int $flags): void
    {
        if ($create = !$this->exists()) { $this->createDirectory(); }
        file_put_contents($this->filename, $contents, $flags);
        if ($create) { chmod($this->filename, 0644); }
    }

    private function createDirectory(): void
    {
        $directoryPath = dirname($this->filename);
        if (is_dir($directoryPath)) { return; }
        mkdir($directoryPath, 0755, true);
    }
}
