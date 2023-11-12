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
use Shudd3r\Filesystem\Generic\Pathname;
use Shudd3r\Filesystem\Generic\FileGenerator;
use Shudd3r\Filesystem\Generic\FileIterator;
use Shudd3r\Filesystem\Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;
use Generator;


class LocalDirectory extends LocalNode implements Directory
{
    /**
     * @param string $path Real path to existing directory
     */
    public static function root(string $path): ?self
    {
        $path   = rtrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
        $isReal = $path === realpath($path) && is_dir($path);
        return $isReal ? new self(Pathname::root($path, DIRECTORY_SEPARATOR)) : null;
    }

    public function exists(): bool
    {
        return is_dir($this->pathname());
    }

    public function create(): void
    {
        if ($this->validated(self::WRITE)->exists()) { return; }
        if (@mkdir($this->pathname(), 0755, true)) { return; }
        throw Exception\IOException\UnableToCreate::node($this);
    }

    public function subdirectory(string $name): self
    {
        return new self($this->pathname->forChildNode($name));
    }

    public function link(string $name): LocalLink
    {
        return new LocalLink($this->pathname->forChildNode($name));
    }

    public function file(string $name): LocalFile
    {
        return new LocalFile($this->pathname->forChildNode($name));
    }

    public function files(): FileIterator
    {
        return new FileIterator(new FileGenerator(fn () => $this->generateFiles()));
    }

    public function asRoot(): self
    {
        if (!$this->pathname->relative()) { return $this; }
        if (!$this->exists()) {
            throw Exception\RootDirectoryNotFound::forRoot($this);
        }

        return new self($this->pathname->asRoot());
    }

    protected function removeNode(): void
    {
        $path = $this->pathname();
        if (!is_link($path)) { $this->removeDescendants(); }
        if (@rmdir($path) || @unlink($path)) { return; }
        throw Exception\IOException\UnableToRemove::node($this);
    }

    private function generateFiles(): Generator
    {
        $filter = fn (string $path) => is_file($path) && !is_link($path);
        foreach ($this->descendantPaths($filter) as $pathname) {
            yield new LocalFile($pathname);
        }
    }

    private function removeDescendants(): void
    {
        foreach ($this->descendantPaths() as $pathname) {
            if ($this->delete($pathname->absolute())) { continue; }
            throw Exception\IOException\UnableToRemove::directoryNode($this, $pathname->absolute());
        }
    }

    private function delete(string $path): bool
    {
        $isWinOS = DIRECTORY_SEPARATOR === '\\';
        $isFile  = $isWinOS ? is_file($path) : is_file($path) || is_link($path);

        $staleWindowsLink = !$isFile && !is_dir($path);
        if ($staleWindowsLink) {
            // @codeCoverageIgnoreStart
            // Cannot determine if it should be removed as file or directory
            return @unlink($path) || @rmdir($path);
            // @codeCoverageIgnoreEnd
        }

        return $isFile ? @unlink($path) : @rmdir($path);
    }

    /**
     * @param ?callable $filter fn(string) => bool
     */
    private function descendantPaths(callable $filter = null): Generator
    {
        if (!$this->exists()) {
            yield from [];
            return;
        }

        $pathname = $this->pathname->absolute();
        $length   = strlen($pathname) + 1;

        $flags = FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_PATHNAME;
        $nodes = new RecursiveDirectoryIterator($pathname, $flags);
        $nodes = new RecursiveIteratorIterator($nodes, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($nodes as $node) {
            if ($filter && !$filter($node)) { continue; }
            yield $this->pathname->forChildNode(substr($node, $length));
        }
    }
}
