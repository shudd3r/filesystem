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
use FilterIterator;
use ArrayIterator;
use Iterator;
use Closure;


class FileList extends FilterIterator implements Files
{
    private ?Closure $filter;
    private ?array   $files = null;

    /**
     * @param Iterator<File> $files
     * @param callable|null  $filter fn(File) => bool
     */
    public function __construct(Iterator $files, callable $filter = null)
    {
        parent::__construct($files);
        $this->filter = $filter ?? fn (File $file) => true;
    }

    public static function fromArray(array $files): self
    {
        $list = new self(new ArrayIterator($files));
        $list->files = $files;
        return $list;
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
        return new self($this->getInnerIterator(), $callback);
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
        return $this->files ??= $this->map();
    }

    public function accept()
    {
        return $this->filter ? ($this->filter)($this->getInnerIterator()->current()) : true;
    }
}
