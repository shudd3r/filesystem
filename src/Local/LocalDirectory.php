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

use Shudd3r\Filesystem\Directory;
use Shudd3r\Filesystem\File;
use Shudd3r\Filesystem\Exception;


class LocalDirectory implements Directory
{
    private string $rootPath;

    private function __construct(string $rootPath)
    {
        $this->rootPath = $rootPath;
    }

    /**
     * @param string $path Real pathname to existing directory
     */
    public static function instance(string $path): ?self
    {
        $path   = rtrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
        $isReal = $path === realpath($path) && is_dir($path);
        return $isReal ? new self($path) : null;
    }

    public function pathname(): string
    {
        return $this->rootPath;
    }

    /**
     * File for this path might not exist, but if this path points
     * to a directory (symlink) or non directory node is found on its
     * path `Exception\InvalidPath` will be thrown.
     *
     * Forward and backward slashes at the beginning of $name argument
     * will be silently removed, and dot path segments (`.`, `..`) are
     * not allowed (`Exception\UnsupportedPathFormat`)
     */
    public function file(string $name): File
    {
        $relativePath = $this->normalizedPath($name);
        $this->absolutePath($relativePath, true);

        return new LocalFile($this, $relativePath);
    }

    /**
     * Directory for this path might not exist, but if this path points
     * to a file (symlink) or non directory node is found on its path
     * `Exception\InvalidPath` will be thrown.
     *
     * Forward and backward slashes at the beginning of $name argument
     * will be silently removed, and dot path segments (`.`, `..`) are
     * not allowed (`Exception\UnsupportedPathFormat`)
     */
    public function subdirectory(string $name): self
    {
        return new self($this->absolutePath($this->normalizedPath($name), false));
    }

    private function absolutePath(string $relativePath, bool $forFile): string
    {
        $path     = '';
        $segments = explode(DIRECTORY_SEPARATOR, $relativePath);
        $basename = array_pop($segments);
        foreach ($segments as $subdirectory) {
            $path = $this->expandedPath($path, $subdirectory, false, $relativePath);
        }

        return $this->rootPath . $this->expandedPath($path, $basename, $forFile, $relativePath);
    }

    private function expandedPath(string $path, string $segment, bool $isFile, string $originalPath): ?string
    {
        if (!$segment) {
            $message = 'Empty path segment in `%s`';
            throw new Exception\InvalidPath(sprintf($message, $originalPath));
        }

        if (in_array($segment, ['.', '..'], true)) {
            $message = 'Dot path segments not allowed for `%s`';
            throw new Exception\InvalidPath(sprintf($message, $originalPath));
        }

        $path     = $path . DIRECTORY_SEPARATOR . $segment;
        $pathname = $this->rootPath . $path;
        $nameCollision = $isFile
            ? is_dir($pathname) || is_link($pathname) && !is_file($pathname)
            : is_file($pathname) || is_link($pathname) && !is_dir($pathname);

        if ($nameCollision) {
            throw Exception\UnreachablePath::for($originalPath, $path, $isFile);
        }

        return $path;
    }

    private function normalizedPath(string $relativePath): string
    {
        return trim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);
    }
}
