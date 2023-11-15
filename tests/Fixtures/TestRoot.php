<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Tests\Fixtures;

use Shudd3r\Filesystem\Generic\Pathname;
use Shudd3r\Filesystem\Directory;
use Shudd3r\Filesystem\File;
use Shudd3r\Filesystem\Link;
use Shudd3r\Filesystem\Node;


abstract class TestRoot
{
    protected Directory $rootDir;
    protected Pathname  $rootPath;

    public function __construct(Directory $rootDir, Pathname $rootPath)
    {
        $this->rootDir  = $rootDir;
        $this->rootPath = $rootPath;
    }

    public function directory(string $name = ''): Directory
    {
        return $name ? $this->rootDir->subdirectory($name) : $this->rootDir;
    }

    public function file(string $name): File
    {
        return $this->rootDir->file($name);
    }

    public function link(string $name): Link
    {
        return $this->rootDir->link($name);
    }

    abstract public function node(string $name = '', bool $typeMatch = true): Node;

    abstract public function assertStructure(array $structure, string $message = ''): void;

    protected function pathname(string $name = ''): Pathname
    {
        return $name ? $this->rootPath->forChildNode($name) : $this->rootPath;
    }
}
