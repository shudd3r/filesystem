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
    private string $absolutePath;
    private string $relativePath;

    public function __construct(Pathname $fileName)
    {
        $this->absolutePath = $fileName->absolute();
        $this->relativePath = $fileName->relative();
    }

    public function pathname(): string
    {
        return $this->absolutePath;
    }

    public function name(): string
    {
        return $this->relativePath;
    }

    public function exists(): bool
    {
        return is_file($this->absolutePath);
    }

    public function remove(): void
    {
        $this->exists() && unlink($this->absolutePath);
    }

    public function contents(): string
    {
        if (!$this->exists()) { return ''; }

        $file = fopen($this->absolutePath, 'rb');
        flock($file, LOCK_SH);
        $contents = file_get_contents($this->absolutePath);
        flock($file, LOCK_UN);
        fclose($file);

        return $contents;
    }

    public function write(string $contents): void
    {
        $this->save($contents, LOCK_EX);
    }

    public function append(string $contents): void
    {
        $this->save($contents, FILE_APPEND);
    }

    private function save(string $contents, int $flags): void
    {
        if ($create = !$this->exists()) { $this->createDirectory(); }
        file_put_contents($this->absolutePath, $contents, $flags);
        if ($create) { chmod($this->absolutePath, 0644); }
    }

    private function createDirectory(): void
    {
        $directoryPath = dirname($this->absolutePath);
        if (is_dir($directoryPath)) { return; }
        mkdir($directoryPath, 0755, true);
    }
}
