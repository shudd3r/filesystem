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


class LocalDirectory
{
    private string $rootPath;

    private function __construct(string $rootPath)
    {
        $this->rootPath = $rootPath;
    }

    public static function instance(string $path): ?self
    {
        $isRealDirectoryPath = $path === realpath($path) && is_dir($path);
        return $isRealDirectoryPath ? new self($path) : null;
    }

    /**
     * @return string Absolute directory pathname
     */
    public function path(): string
    {
        return $this->rootPath;
    }

    public function expandedWith(string $relativePathname): ?string
    {
        $name = basename($relativePathname);
        $path = '';
        foreach ($this->segments(dirname($relativePathname)) as $subdirectory) {
            $path .= DIRECTORY_SEPARATOR . $subdirectory;
            if (is_file($this->rootPath . $path)) { return null; }
        }
        return $this->rootPath . $path . DIRECTORY_SEPARATOR . $name;
    }

    private function segments(string $relativePath): array
    {
        return explode('/', trim(str_replace('\\', '/', $relativePath), '/'));
    }
}
