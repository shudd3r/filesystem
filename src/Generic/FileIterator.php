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

use Shudd3r\Filesystem\File;
use IteratorAggregate;
use Traversable;
use ArrayIterator;
use Closure;


class FileIterator implements IteratorAggregate
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

    /**
     * @param callable $match fn(File) => bool
     */
    public function find(callable $match): ?File
    {
        foreach ($this as $file) {
            if ($match($file)) { return $file; }
        }
        return null;
    }

    /**
     * @param callable $accept fn(File) => bool
     */
    public function select(callable $accept): self
    {
        $accept = $this->filter ? fn (File $file) => ($this->filter)($file) && $accept($file) : $accept;
        return new self($this->files, $accept);
    }

    /**
     * @param callable $fileAction fn(File) => void
     */
    public function forEach(callable $fileAction): void
    {
        foreach ($this as $file) {
            $fileAction($file);
        }
    }

    /**
     * @template Type
     *
     * @param callable|null $transformFile fn(File) => Type
     *
     * @return array<Type>
     */
    public function map(callable $transformFile = null): array
    {
        $items = [];
        foreach ($this as $file) {
            $items[] = $transformFile ? $transformFile($file) : $file;
        }
        return $items;
    }

    /**
     * @return array<File>
     */
    public function list(): array
    {
        return $this->map();
    }

    public function getIterator(): Traversable
    {
        foreach ($this->files as $file) {
            if ($this->filter && !($this->filter)($file)) { continue; }
            yield $file;
        }
    }
}
