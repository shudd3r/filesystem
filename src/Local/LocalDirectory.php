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
use Shudd3r\Filesystem\Local\PathName\DirectoryName;
use Shudd3r\Filesystem\Generic\FileList;
use Shudd3r\Filesystem\Files;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;
use CachingIterator;
use Generator;


class LocalDirectory implements Directory
{
    private DirectoryName $path;

    public function __construct(DirectoryName $path)
    {
        $this->path = $path;
    }

    /**
     * @param string $path Real pathname to existing directory
     */
    public static function root(string $path): ?self
    {
        $path = DirectoryName::root($path);
        return $path ? new self($path) : null;
    }

    public function pathname(): string
    {
        return (string) $this->path;
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
    public function file(string $name): LocalFile
    {
        return new LocalFile($this->path->file($name));
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
        return new self($this->path->directory($name));
    }

    public function files(): Files
    {
        $flags = FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_PATHNAME;
        $nodes = new RecursiveDirectoryIterator((string) $this->path, $flags);
        $nodes = new RecursiveIteratorIterator($nodes, RecursiveIteratorIterator::CHILD_FIRST);
        $files = new CachingIterator($this->generateFile($nodes), CachingIterator::FULL_CACHE);
        return new FileList($files);
    }

    private function generateFile(\Iterator $nodes): Generator
    {
        $rootLength = strlen((string) $this->path);
        $relative   = fn (string $path) => substr($path, $rootLength);
        foreach ($nodes as $name) {
            if (!is_file($name)) { continue; }
            yield new LocalFile($this->path->file($relative($name)));
        }
    }
}
