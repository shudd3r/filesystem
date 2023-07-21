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
use Shudd3r\Filesystem\Generic\FileList;
use Shudd3r\Filesystem\Generic\FileGenerator;
use Shudd3r\Filesystem\Files;
use Generator;


class LocalDirectory implements Directory
{
    private Pathname $path;

    public function __construct(Pathname $path)
    {
        $this->path = $path;
    }

    /**
     * @param string $path Real pathname to existing directory
     */
    public static function root(string $path): ?self
    {
        $path = Pathname::root($path);
        return $path ? new self($path) : null;
    }

    public function pathname(): string
    {
        return $this->path->absolute();
    }

    public function name(): string
    {
        return $this->path->relative();
    }

    public function exists(): bool
    {
        return is_dir($this->pathname());
    }

    public function isReadable(): bool
    {
        if ($this->exists()) { return is_readable($this->pathname()); }
        $ancestor = $this->path->closestAncestor();
        return is_dir($ancestor) && is_readable($ancestor);
    }

    public function isWritable(): bool
    {
        if ($this->exists()) { return is_writable($this->pathname()); }
        $ancestor = $this->path->closestAncestor();
        return is_dir($ancestor) && is_writable($ancestor);
    }

    public function remove(): void
    {
        if (!$this->exists()) { return; }

        $this->removeDescendants();
        rmdir($this->pathname());
    }

    /**
     * Superfluous path separators at the beginning or the end of
     * the name are ignored, but only canonical paths are allowed.
     * For empty or dot path segments `InvalidPath` exception is
     * thrown.
     */
    public function file(string $name): LocalFile
    {
        return new LocalFile($this->path->forChildNode($name));
    }

    /**
     * Superfluous path separators at the beginning or the end of
     * the name are ignored, but only canonical paths are allowed.
     * For empty or dot path segments `InvalidPath` exception is
     * thrown.
     */
    public function subdirectory(string $name): self
    {
        return new self($this->path->forChildNode($name));
    }

    public function files(): Files
    {
        return new FileList(new FileGenerator(fn () => $this->generateFiles()));
    }

    public function asRoot(): self
    {
        return $this->path->relative() ? new self($this->path->asRoot()) : $this;
    }

    private function generateFiles(): Generator
    {
        $filter = fn (string $path) => is_file($path);
        foreach ($this->path->descendantPaths($filter) as $pathname) {
            yield new LocalFile($pathname);
        }
    }

    private function removeDescendants(): void
    {
        foreach ($this->path->descendantPaths() as $pathname) {
            $this->removeNode($pathname->absolute());
        }
    }

    private function removeNode(string $path): void
    {
        $isWinOS = DIRECTORY_SEPARATOR === '\\';
        $isFile  = $isWinOS ? is_file($path) : is_file($path) || is_link($path);

        $staleWindowsLink = !$isFile && !is_dir($path);
        if ($staleWindowsLink) {
            // Cannot determine if it should be removed as file or directory
            @unlink($path) || rmdir($path);
            return;
        }

        $isFile ? unlink($path) : rmdir($path);
    }
}
