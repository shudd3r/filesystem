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

use Traversable;


interface Files extends Traversable
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
     * @template Type
     *
     * @param callable $callback fn(File) => Type
     *
     * @return array<Type>
     */
    public function map(callable $callback): array;

    /**
     * @return array<File>
     */
    public function list(): array;
}