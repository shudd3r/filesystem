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
use Shudd3r\Filesystem\Generic\FileGenerator;
use Shudd3r\Filesystem\Files;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;
use Generator;
use Iterator;


class LocalDirectory implements Directory
{
    private DirectoryName $path;

    /**
     * DirectoryName value object ensures that path to directory either
     * already exists or is potentially valid (is not currently a file
     * nor a file symlink).
     *
     * @param DirectoryName $path
     */
    public function __construct(DirectoryName $path)
    {
        $this->path = $path;
    }

    /**
     * @param string $path Real pathname to existing directory
     */
    public static function root(string $path): ?self
    {
        $path = DirectoryName::forRootPath($path);
        return $path ? new self($path) : null;
    }

    public function pathname(): string
    {
        return $this->path->absolute();
    }

    /**
     * Superfluous path separators at the beginning or the end of
     * the name are ignored, but only canonical paths are allowed.
     * For empty or dot path segments `InvalidPath` exception is
     * thrown.
     */
    public function file(string $name): LocalFile
    {
        return new LocalFile($this->path->file($name));
    }

    /**
     * Superfluous path separators at the beginning or the end of
     * the name are ignored, but only canonical paths are allowed.
     * For empty or dot path segments `InvalidPath` exception is
     * thrown.
     */
    public function subdirectory(string $name): self
    {
        return new self($this->path->directory($name));
    }

    public function files(): Files
    {
        $flags = FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_PATHNAME;
        $nodes = new RecursiveDirectoryIterator($this->path->absolute(), $flags);
        $nodes = new RecursiveIteratorIterator($nodes, RecursiveIteratorIterator::CHILD_FIRST);

        return new FileList(new FileGenerator(fn () => $this->generateFile($nodes)));
    }

    private function generateFile(Iterator $nodes): Generator
    {
        $pathLength = strlen($this->path->absolute()) + 1;
        $relative   = fn (string $path) => substr($path, $pathLength);
        foreach ($nodes as $name) {
            if (!is_file($name)) { continue; }
            yield new LocalFile($this->path->file($relative($name)));
        }
    }
}
