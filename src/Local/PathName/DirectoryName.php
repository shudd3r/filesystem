<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Local\PathName;

use Shudd3r\Filesystem\Exception\DirectoryDoesNotExist;
use Shudd3r\Filesystem\Local\Pathname;
use Shudd3r\Filesystem\Exception\UnreachablePath;
use Shudd3r\Filesystem\Exception\InvalidPath;


/**
 * Value Object which ensures that directory of this name either exists or
 * can be created with adequate access permissions.
 *
 * This subtype can be instantiated only through static constructor or
 * derived from root path with `self::directory()` method.
 */
final class DirectoryName extends Pathname
{
    /**
     * @param string $path Real, absolute path to existing directory
     */
    public static function forRootPath(string $path): ?self
    {
        $path   = rtrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
        $isReal = $path === realpath($path) && is_dir($path);
        return $isReal ? new self($path) : null;
    }

    /**
     * File with this name might not exist, but if given path points to
     * a directory (symlink) or non directory node is found on its path
     * `UnreachablePath` will be thrown.
     *
     * Forward and backward slashes at the beginning and the end of name
     * will be silently removed, and either empty or dot path segments are
     * not allowed and method will throw `InvalidPath` exception.
     *
     * @param string $name File basename or relative file pathname
     *
     * @throws UnreachablePath|InvalidPath
     */
    public function file(string $name): FileName
    {
        return $this->filename($this->relativePath($name, true));
    }

    /**
     * Directory with this name might not exist, but if given path points
     * to a file (symlink) or non directory node is found on its path
     * `UnreachablePath` will be thrown.
     *
     * Forward and backward slashes at the beginning and the end of name
     * will be silently removed, and either empty or dot path segments are
     * not allowed and method will throw `InvalidPath` exception.
     *
     * @param string $name Directory basename or relative directory pathname
     *
     * @throws InvalidPath|UnreachablePath
     *
     * @return self Directory name relative to root directory
     */
    public function directory(string $name): self
    {
        return new self($this->root, $this->relativePath($name, false));
    }

    /**
     * @throws DirectoryDoesNotExist For not existing directory
     *
     * @return self Directory name without relative path
     */
    public function asRoot(): self
    {
        if (!$this->name) { return $this; }
        $pathname = $this->absolute();
        if (!is_dir($pathname)) {
            throw DirectoryDoesNotExist::forRoot($this->root, $this->name);
        }
        return new self($pathname);
    }

    private function relativePath(string $name, bool $forFile): string
    {
        $relative = $this->normalizedPath($name, $forFile);
        if ($collision = $this->collidingPath($relative, $forFile)) {
            throw UnreachablePath::for($relative, $collision, $forFile);
        }

        return $relative;
    }

    private function normalizedPath(string $name, bool $forFile): string
    {
        $type = $forFile ? 'file' : 'directory';
        $name = trim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $name), DIRECTORY_SEPARATOR);
        if (!$name) {
            $message = 'Name given for %s is empty';
            throw new InvalidPath(sprintf($message, $type));
        }

        if ($this->hasSegment($name, '')) {
            $message = 'Empty path segment in `%s` %s path';
            throw new InvalidPath(sprintf($message, $name, $type));
        }

        if ($this->hasSegment($name, '..', '.')) {
            $message = 'Dot segments not allowed for `%s` %s path';
            throw new InvalidPath(sprintf($message, $name, $type));
        }

        return $name;
    }

    private function collidingPath(string $name, bool $forFile): ?string
    {
        $pathname = $this->root . DIRECTORY_SEPARATOR . $name;
        if ($this->exists($pathname, $forFile)) { return null; }

        $path     = '';
        $segments = explode(DIRECTORY_SEPARATOR, $name);
        $basename = array_pop($segments);
        foreach ($segments as $subdirectory) {
            $path = $path . DIRECTORY_SEPARATOR . $subdirectory;
            if (!$this->isValidPath($path, false)) { return $path; }
        }

        $path = $path . DIRECTORY_SEPARATOR . $basename;
        return $this->isValidPath($path, $forFile) ? null : $path;
    }

    private function hasSegment(string $name, string ...$segments): bool
    {
        $name = $this->pathFragment($name);
        foreach ($segments as $segment) {
            $fragmentFound = strpos($name, $this->pathFragment($segment)) !== false;
            if ($fragmentFound) { return true; }
        }
        return false;
    }

    private function pathFragment(string $segment): string
    {
        return DIRECTORY_SEPARATOR . $segment . DIRECTORY_SEPARATOR;
    }

    private function isValidPath(string $path, bool $forFile): bool
    {
        $pathname = $this->root . $path;
        if ($this->exists($pathname, $forFile)) { return true; }

        $collides = $forFile ? is_dir($pathname) : is_file($pathname);
        return !$collides && !is_link($pathname);
    }

    private function exists(string $pathname, bool $isFile): bool
    {
        return $isFile ? is_file($pathname) : is_dir($pathname);
    }
}
