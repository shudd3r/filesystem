<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem;

use Iterator;


interface Files extends Iterator
{
    /**
     * @param callable $callback fn(File) => bool
     */
    public function find(callable $callback): ?File;

    /**
     * @param callable $callback fn(File) => bool
     */
    public function select(callable $callback): self;

    /**
     * @param callable $callback fn(File) => void
     */
    public function forEach(callable $callback): void;

    /**
     * @template T
     *
     * @param callable $callback fn(File) => T
     *
     * @return array<T>
     */
    public function map(callable $callback): array;

    /**
     * @return array
     */
    public function list(): array;
}
