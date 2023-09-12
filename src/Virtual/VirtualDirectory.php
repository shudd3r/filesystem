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
use Shudd3r\Filesystem\Generic\FileIterator;
use Shudd3r\Filesystem\Generic\FileGenerator;
use Shudd3r\Filesystem\Exception;
use Generator;


class VirtualDirectory extends VirtualNode implements Directory
{
    public function subdirectory(string $name): VirtualDirectory
    {
        return new VirtualDirectory($this->nodes, $this->root, $this->expandedName($name));
    }

    public function link(string $name): VirtualLink
    {
        return new VirtualLink($this->nodes, $this->root, $this->expandedName($name));
    }

    public function file(string $name): VirtualFile
    {
        return new VirtualFile($this->nodes, $this->root, $this->expandedName($name));
    }

    public function files(): FileIterator
    {
        return new FileIterator(new FileGenerator(fn () => $this->generateFiles()));
    }

    public function asRoot(): VirtualDirectory
    {
        if (!$this->name) { return $this; }
        if (!$this->exists()) {
            throw Exception\RootDirectoryNotFound::forRoot($this->rootPath(), $this->name);
        }
        return new self($this->nodes, $this->rootPath(), '');
    }

    protected function nodeExists(NodeData $node): bool
    {
        return $node->isDir();
    }

    private function expandedName(string $name): string
    {
        return $this->name ? $this->name . '/' . $name : $name;
    }

    private function generateFiles(): Generator
    {
        foreach ($this->nodeData()->filenames() as $filename) {
            yield new VirtualFile($this->nodes, $this->root, $this->expandedName($filename));
        }
    }
}
