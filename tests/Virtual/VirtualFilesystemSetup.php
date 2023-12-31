<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Tests\Virtual;

use Shudd3r\Filesystem\Tests\Fixtures\TestRoot\VirtualTestRoot;
use Shudd3r\Filesystem\Generic\Pathname;


trait VirtualFilesystemSetup
{
    protected function root(array $structure = null, array $access = [], Pathname $pathname = null): VirtualTestRoot
    {
        return new VirtualTestRoot($pathname, $structure ?? $this->exampleStructure(), $access);
    }

    protected function path(string $name = ''): string
    {
        $path = $name ? Pathname::root('vfs://')->forChildNode($name) : Pathname::root('vfs://');
        return $path->absolute();
    }
}
