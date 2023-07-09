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

use Shudd3r\Filesystem\Local\Pathname;
use Shudd3r\Filesystem\Exception;


class DirectoryName extends Pathname
{
    public static function root(string $path): ?self
    {
        $path   = rtrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
        $isReal = $path === realpath($path) && is_dir($path);
        return $isReal ? new self($path) : null;
    }

    public function file(string $name): FileName
    {
        return $this->filename($this->relativePath($name, true));
    }

    public function directory(string $name): self
    {
        return new self($this->path . DIRECTORY_SEPARATOR . $this->relativePath($name, false));
    }

    protected function relativePath(string $name, bool $forFile): string
    {
        $name     = $this->normalizedPath($name, $forFile ? 'file' : 'directory');
        $path     = '';
        $segments = explode(DIRECTORY_SEPARATOR, $name);
        $basename = array_pop($segments);
        foreach ($segments as $subdirectory) {
            $path = $this->expandedPath($path, $subdirectory, false, $name);
        }

        return substr($this->expandedPath($path, $basename, $forFile, $name), 1);
    }

    private function expandedPath(string $path, string $segment, bool $isFile, string $originalPath): string
    {
        $path     = $path . DIRECTORY_SEPARATOR . $segment;
        $pathname = $this->path . $path;
        $nameCollision = $isFile
            ? is_dir($pathname) || is_link($pathname) && !is_file($pathname)
            : is_file($pathname) || is_link($pathname) && !is_dir($pathname);

        if ($nameCollision) {
            throw Exception\UnreachablePath::for($originalPath, $path, $isFile);
        }

        return $path;
    }

    private function normalizedPath(string $name, string $nodeType): string
    {
        $name = trim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $name), DIRECTORY_SEPARATOR);
        if (!$name) {
            $message = 'Name given for %s is empty';
            throw new Exception\InvalidPath(sprintf($message, $nodeType));
        }

        if ($this->hasSegment($name, '')) {
            $message = 'Empty path segment in `%s` %s path';
            throw new Exception\InvalidPath(sprintf($message, $name, $nodeType));
        }

        if ($this->hasSegment($name, '..', '.')) {
            $message = 'Dot segments not allowed for `%s` %s path';
            throw new Exception\InvalidPath(sprintf($message, $name, $nodeType));
        }

        return $name;
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
}
