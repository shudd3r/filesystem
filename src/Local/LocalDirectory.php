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
        $path   = rtrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
        $isReal = $path === realpath($path) && is_dir($path);
        return $isReal ? new self($path) : null;
    }

    /**
     * @return string Absolute directory pathname
     */
    public function path(): string
    {
        return $this->rootPath;
    }

    public function filePath(string $name): ?string
    {
        return $this->expandedPath($name, true);
    }

    public function subdirectoryPath(string $name): ?string
    {
        return $this->expandedPath($name, false);
    }

    private function expandedPath(string $relativePathname, bool $forFile): ?string
    {
        $name = basename($relativePathname);
        $path = '';
        foreach ($this->pathSegments(dirname($relativePathname)) as $subdirectory) {
            $path .= DIRECTORY_SEPARATOR . $subdirectory;
            if ($this->hasPathCollision($path)) { return null; }
        }
        $path = $path . DIRECTORY_SEPARATOR . $name;
        return $this->hasPathCollision($path, $forFile) ? null : $this->rootPath . $path;
    }

    private function hasPathCollision($path, bool $forFilename = false): bool
    {
        $pathname = $this->rootPath . $path;
        return $forFilename
            ? is_dir($pathname) || is_link($pathname) && !is_file($pathname)
            : is_file($pathname) || is_link($pathname) && !is_dir($pathname);
    }

    private function pathSegments(string $relativePath): array
    {
        $relativePath = trim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);
        return explode(DIRECTORY_SEPARATOR, $relativePath);
    }
}
