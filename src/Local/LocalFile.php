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
use Shudd3r\Filesystem\Exception\IOException;


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

        if ($contents === false) {
            throw IOException\UnableToReadContents::fromFile($this);
        }

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

    protected function removeNode(): void
    {
        if (!@unlink($this->pathname->absolute())) {
            throw IOException\UnableToRemove::node($this);
        }
    }

    private function save(string $contents, int $flags): void
    {
        if ($create = !$this->exists()) { $this->createDirectory(); }
        if (@file_put_contents($this->pathname->absolute(), $contents, $flags) === false) {
            throw $create ? IOException\UnableToCreate::node($this) : IOException\UnableToWriteContents::toFile($this);
        }
        if (!$create) { return; }
        if (!@chmod($this->pathname->absolute(), 0644)) {
            throw IOException\UnableToSetPermissions::forNode($this);
        }
    }

    private function createDirectory(): void
    {
        $directoryPath = dirname($this->pathname->absolute());
        if (is_dir($directoryPath)) { return; }
        if (!@mkdir($directoryPath, 0755, true)) {
            throw IOException\UnableToCreate::directories($this);
        }
    }
}
