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


class Path
{
    private string $path;

    private function __construct(string $path)
    {
        $this->path = $path;
    }

    public static function fromString(string $path): ?self
    {
        if (!is_dir($path) && !is_file($path)) { return null; }

        $realpath = realpath($path);
        if ($realpath !== $path && !is_link($path)) { return null; }
        return $realpath ? new self($realpath) : null;
    }

    /**
     * @return string Absolute pathname string
     */
    public function __toString(): string
    {
        return $this->path;
    }
}
