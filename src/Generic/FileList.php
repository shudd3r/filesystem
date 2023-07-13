<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Generic;

use Shudd3r\Filesystem\Files;
use Shudd3r\Filesystem\File;
use IteratorAggregate;
use Traversable;
use ArrayIterator;
use Iterator;
use Closure;


class FileList implements Files, IteratorAggregate
{
    private Traversable $files;
    private ?Closure    $filter;

    /**
     * @param Traversable<File> $files
     * @param callable|null     $filter fn(File) => bool
     */
    public function __construct(Traversable $files, ?callable $filter = null)
    {
        $this->files  = $files;
        $this->filter = $filter;
    }

    /**
     * @param array<File> $files
     */
    public static function fromArray(array $files): self
    {
        return new self(new ArrayIterator($files));
    }

    public function find(callable $callback): ?File
    {
        foreach ($this as $file) {
            if ($callback($file)) { return $file; }
        }
        return null;
    }

    public function select(callable $callback): Files
    {
        $callback = $this->filter ? fn (File $file) => ($this->filter)($file) && $callback($file) : $callback;
        return new self($this->files, $callback);
    }

    public function forEach(callable $callback): void
    {
        foreach ($this as $file) {
            $callback($file);
        }
    }

    public function map(callable $callback = null): array
    {
        $items = [];
        foreach ($this as $file) {
            $items[] = $callback ? $callback($file) : $file;
        }
        return $items;
    }

    public function list(): array
    {
        return $this->map();
    }

    public function getIterator(): Iterator
    {
        foreach ($this->files as $file) {
            if ($this->filter && !($this->filter)($file)) { continue; }
            yield $file;
        }
    }
}
