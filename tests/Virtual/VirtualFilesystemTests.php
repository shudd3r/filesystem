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

use Shudd3r\Filesystem\Tests\FilesystemTests;
use Shudd3r\Filesystem\Generic\Pathname;
use Shudd3r\Filesystem\Tests\Fixtures\TestRoot;


abstract class VirtualFilesystemTests extends FilesystemTests
{
    protected function root(array $structure = null): TestRoot\VirtualTestRoot
    {
        return new TestRoot\VirtualTestRoot(Pathname::root('vfs://'), $structure ?? $this->exampleStructure());
    }

    protected function path(string $name = ''): string
    {
        $path = $name ? Pathname::root('vfs://')->forChildNode($name) : Pathname::root('vfs://');
        return $path->absolute();
    }
}
