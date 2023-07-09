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

use Shudd3r\Filesystem\Exception\InvalidPath;
use Shudd3r\Filesystem\Exception\UnreachablePath;


interface Directory
{
    /**
     * @return string Absolute directory pathname
     */
    public function pathname(): string;

    /**
     * @param string $name File basename or relative file pathname
     *
     * @throws InvalidPath|UnreachablePath
     *
     * @return File
     */
    public function file(string $name): File;

    /**
     * @param string $name Directory basename or relative directory pathname
     *
     * @throws InvalidPath|UnreachablePath
     *
     * @return self
     */
    public function subdirectory(string $name): self;
}