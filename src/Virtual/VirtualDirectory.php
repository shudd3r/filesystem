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

use Shudd3r\Filesystem\Exception;


class VirtualDirectory extends VirtualNode
{
    public function subdirectory(string $name): VirtualDirectory
    {
        return new VirtualDirectory($this->nodes, $this->root, $this->expandedName($name));
    }

    public function file(string $name): VirtualFile
    {
        return new VirtualFile($this->nodes, $this->root, $this->expandedName($name));
    }

    public function link(string $name): VirtualLink
    {
        return new VirtualLink($this->nodes, $this->root, $this->expandedName($name));
    }

    public function asRoot(): VirtualDirectory
    {
        if (!$this->name) { return $this; }
        if (!$this->exists()) {
            throw new Exception\RootDirectoryNotFound();
        }
        return new self($this->nodes, $this->pathname(), '');
    }

    private function expandedName(string $name): string
    {
        return $this->name ? $this->name . '/' . $name : $name;
    }
}
