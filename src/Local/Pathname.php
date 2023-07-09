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

use Shudd3r\Filesystem\Local\PathName\FileName;


class Pathname
{
    protected string $path;

    protected function __construct(string $path)
    {
        $this->path = $path;
    }

    public function __toString(): string
    {
        return $this->path;
    }

    protected function filename(string $name): FileName
    {
        return new FileName($this->path, $name);
    }
}
