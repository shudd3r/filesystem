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


class LocalFile extends LocalNode implements File
{
    public function exists(): bool
    {
        return is_file($this->pathname->absolute());
    }

    public function remove(): void
    {
        if (!$this->exists()) { return; }
        $this->validated(self::REMOVE);
        unlink($this->pathname->absolute());
    }

    public function contents(): string
    {
        if (!$this->validated(self::READ)->exists()) { return ''; }

        $file = fopen($this->pathname->absolute(), 'rb');
        flock($file, LOCK_SH);
        $contents = file_get_contents($this->pathname->absolute());
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
        file_put_contents($this->pathname->absolute(), $contents, $flags);
        if ($create) { chmod($this->pathname->absolute(), 0644); }
    }

    private function createDirectory(): void
    {
        $directoryPath = dirname($this->pathname->absolute());
        if (is_dir($directoryPath)) { return; }
        mkdir($directoryPath, 0755, true);
    }
}
