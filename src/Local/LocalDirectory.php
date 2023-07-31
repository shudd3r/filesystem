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
use Shudd3r\Filesystem\Generic\FileIterator;
use Shudd3r\Filesystem\Generic\FileGenerator;
use Shudd3r\Filesystem\Exception\IOException;
use Generator;


class LocalDirectory extends LocalNode implements Directory
{
    private ?int $assert;

    public function __construct(Pathname $pathname, int $assert = null)
    {
        $this->assert = $assert;
        parent::__construct($pathname);
    }

    /**
     * @param string $path   Real path to existing directory
     * @param ?int   $assert Flags for derived Nodes assertions
     *
     * @see Node::validated()
     */
    public static function root(string $path, int $assert = null): ?self
    {
        $path = Pathname::root($path);
        return $path ? new self($path, $assert) : null;
    }

    public function exists(): bool
    {
        return is_dir($this->pathname());
    }

    public function subdirectory(string $name): self
    {
        $directory = new self($this->pathname->forChildNode($name), $this->assert);
        return isset($this->assert) ? $directory->validated($this->assert) : $directory;
    }

    public function link(string $name): LocalLink
    {
        $link = new LocalLink($this->pathname->forChildNode($name));
        return isset($this->assert) ? $link->validated($this->assert) : $link;
    }

    public function file(string $name): LocalFile
    {
        $file = new LocalFile($this->pathname->forChildNode($name));
        return isset($this->assert) ? $file->validated($this->assert) : $file;
    }

    public function files(): FileIterator
    {
        return new FileIterator(new FileGenerator(fn () => $this->generateFiles()));
    }

    public function asRoot(): self
    {
        return $this->pathname->relative() ? new self($this->pathname->asRoot(), $this->assert) : $this;
    }

    protected function removeNode(): void
    {
        foreach ($this->pathname->descendantPaths() as $pathname) {
            if (!$this->delete($pathname->absolute())) {
                throw IOException\UnableToRemove::directoryNode($this, $pathname->absolute());
            }
        }
        if (!@rmdir($this->pathname())) {
            throw IOException\UnableToRemove::node($this);
        }
    }

    private function generateFiles(): Generator
    {
        $filter = fn (string $path) => is_file($path);
        foreach ($this->pathname->descendantPaths($filter) as $pathname) {
            yield new LocalFile($pathname);
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
}
