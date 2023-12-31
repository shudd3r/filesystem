<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Virtual;

use Shudd3r\Filesystem\Directory;
use Shudd3r\Filesystem\Generic\Pathname;
use Shudd3r\Filesystem\Generic\FileIterator;
use Shudd3r\Filesystem\Generic\FileGenerator;
use Shudd3r\Filesystem\Virtual\Nodes\Node;
use Shudd3r\Filesystem\Virtual\Nodes\TreeNode\Directory as RootDirectory;
use Shudd3r\Filesystem\Exception;
use Generator;


class VirtualDirectory extends VirtualNode implements Directory
{
    /**
     * @param ?Pathname      $pathname  Pathname of root directory
     * @param ?RootDirectory $directory Pre-existing TreeNode structure
     */
    public static function root(?Pathname $pathname = null, RootDirectory $directory = null): self
    {
        $rootPath = $pathname ? $pathname->asRoot() : Pathname::root('vfs://');
        return new self(new Nodes($rootPath, $directory ?? new RootDirectory()), $rootPath);
    }

    public function create(): void
    {
        $node = $this->validated(self::WRITE)->node();
        if ($this->nodeExists($node)) { return; }
        $node->createDir();
    }

    public function subdirectory(string $name): VirtualDirectory
    {
        return new VirtualDirectory($this->nodes, $this->path->forChildNode($name));
    }

    public function link(string $name): VirtualLink
    {
        return new VirtualLink($this->nodes, $this->path->forChildNode($name));
    }

    public function file(string $name): VirtualFile
    {
        return new VirtualFile($this->nodes, $this->path->forChildNode($name));
    }

    public function files(): FileIterator
    {
        return new FileIterator(new FileGenerator(fn () => $this->generateFiles()));
    }

    public function asRoot(): VirtualDirectory
    {
        $path = $this->path->asRoot();
        if ($path === $this->path) { return $this; }
        if (!$this->exists()) {
            throw Exception\RootDirectoryNotFound::forRoot($this);
        }
        return new self($this->nodes, $path);
    }

    protected function nodeExists(Node $node): bool
    {
        return $node->isDir();
    }

    private function generateFiles(): Generator
    {
        $node      = $this->node();
        $filenames = $node->isDir() ? $node->filenames() : [];

        foreach ($filenames as $filename) {
            yield new VirtualFile($this->nodes, $this->path->forChildNode($filename));
        }
    }
}
