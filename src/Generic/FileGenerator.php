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

use IteratorAggregate;
use Shudd3r\Filesystem\File;
use Traversable;
use Closure;


class FileGenerator implements IteratorAggregate
{
    private Closure $iterator;

    /**
     * @param callable $iterator fn() => Traversable<File>
     */
    public function __construct(callable $iterator)
    {
        $this->iterator = $iterator;
    }

    /**
     * @return Traversable<File>
     */
    public function getIterator(): Traversable
    {
        return ($this->iterator)();
    }
}
