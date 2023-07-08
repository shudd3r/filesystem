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

use Shudd3r\Filesystem\Exception\InvalidPath;
use Shudd3r\Filesystem\Exception\UnreachablePath;


class LocalDirectory
{
    private string $rootPath;

    private function __construct(string $rootPath)
    {
        $this->rootPath = $rootPath;
    }

    /**
     * @param string $path Real path to existing directory
     */
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

    /**
     * Absolute filename path in this directory.
     *
     * File for this path might not exist, but if this path points
     * to a directory (symlink) or non directory node is found on its
     * path `Exception\InvalidPath` will be thrown.
     *
     * Forward and backward slashes at the beginning of $name argument
     * will be silently removed, and dot path segments (`.`, `..`) are
     * not allowed (`Exception\UnsupportedPathFormat`)
     *
     * @param string $name relative file name
     *
     * @throws InvalidPath|UnreachablePath
     *
     * @return string
     */
    public function filePath(string $name): string
    {
        return $this->absolutePath($this->normalizedPath($name), true);
    }

    /**
     * Absolute subdirectory path.
     *
     * Directory for this path might not exist, but if this path points
     * to a file (symlink) or non directory node is found on its path
     * `Exception\InvalidPath` will be thrown.
     *
     * Forward and backward slashes at the beginning of $name argument
     * will be silently removed, and dot path segments (`.`, `..`) are
     * not allowed (`Exception\UnsupportedPathFormat`)
     *
     * @param string $name relative directory name
     *
     * @throws InvalidPath|UnreachablePath
     *
     * @return string
     */
    public function subdirectoryPath(string $name): string
    {
        return $this->absolutePath($this->normalizedPath($name), false);
    }

    /** @throws InvalidPath|UnreachablePath */
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

    /** @throws InvalidPath|UnreachablePath */
    private function expandedPath(string $path, string $segment, bool $isFile, string $originalPath): ?string
    {
        if (!$segment) {
            $message = 'Empty path segment in `%s`';
            throw new InvalidPath(sprintf($message, $originalPath));
        }

        if (in_array($segment, ['.', '..'], true)) {
            $message = 'Dot path segments not allowed for `%s`';
            throw new InvalidPath(sprintf($message, $originalPath));
        }

        $path     = $path . DIRECTORY_SEPARATOR . $segment;
        $pathname = $this->rootPath . $path;
        $nameCollision = $isFile
            ? is_dir($pathname) || is_link($pathname) && !is_file($pathname)
            : is_file($pathname) || is_link($pathname) && !is_dir($pathname);

        if ($nameCollision) {
            throw UnreachablePath::for($originalPath, $path, $isFile);
        }

        return $path;
    }

    private function normalizedPath(string $relativePath): string
    {
        return trim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);
    }
}
