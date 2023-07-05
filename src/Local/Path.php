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

    public static function fromString(string $absolutePathname): ?self
    {
        if (!is_dir($absolutePathname) && !is_file($absolutePathname)) { return null; }

        $realpath = realpath($absolutePathname);
        if ($realpath !== $absolutePathname && !is_link($absolutePathname)) { return null; }
        return $realpath ? new self($realpath) : null;
    }

    /**
     * @return string Absolute pathname string
     */
    public function __toString(): string
    {
        return $this->path;
    }

    public function expandedWith(string $relativePathname): ?string
    {
        if (!is_dir($this->path)) { return null; }

        $name = basename($relativePathname);
        $path = '';
        foreach ($this->segments(dirname($relativePathname)) as $subdirectory) {
            $path .= DIRECTORY_SEPARATOR . $subdirectory;
            if (is_file($this->path . $path)) { return null; }
        }
        return $this->path . $path . DIRECTORY_SEPARATOR . $name;
    }

    private function segments(string $relativePath): array
    {
        return explode('/', trim(str_replace('\\', '/', $relativePath), '/'));
    }
}
