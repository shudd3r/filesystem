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


class LocalDirectory extends LocalNode implements Directory
{
    protected bool $isFile = false;

    /**
     * @param string $path Real pathname to existing directory
     */
    public static function root(string $path): ?self
    {
        $path = Pathname::root($path);
        return $path ? new self($path) : null;
    }

    public function exists(): bool
    {
        return is_dir($this->pathname());
    }

    public function remove(): void
    {
        if (!$this->exists()) { return; }

        $this->validated(self::REMOVE)->removeDescendants();
        rmdir($this->pathname());
    }

    /**
     * Only canonical paths are allowed, and superfluous path separators
     * at the beginning or the end of the name will be trimmed. For empty
     * or dot path segments `InvalidPath` exception is thrown.
     */
    public function file(string $name): LocalFile
    {
        return new LocalFile($this->pathname->forChildNode($name));
    }

    /**
     * Only canonical paths are allowed, and superfluous path separators
     * at the beginning or the end of the name will be trimmed. For empty
     * or dot path segments `InvalidPath` exception is thrown.
     */
    public function subdirectory(string $name): self
    {
        return new self($this->pathname->forChildNode($name));
    }

    public function files(): Files
    {
        return new FileList(new FileGenerator(fn () => $this->generateFiles()));
    }

    public function asRoot(): self
    {
        return $this->pathname->relative() ? new self($this->pathname->asRoot()) : $this;
    }

    private function generateFiles(): Generator
    {
        $filter = fn (string $path) => is_file($path);
        foreach ($this->pathname->descendantPaths($filter) as $pathname) {
            yield new LocalFile($pathname);
        }
    }

    private function removeDescendants(): void
    {
        foreach ($this->pathname->descendantPaths() as $pathname) {
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
