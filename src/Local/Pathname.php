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

use Shudd3r\Filesystem\Local\PathName\FileName;


/**
 * Base class for validated & normalized local filesystem paths.
 *
 * This type cannot be instantiated on its own.
 */
abstract class Pathname
{
    protected string $root;
    protected string $name;

    protected function __construct(string $root, string $name = '')
    {
        $this->root = $root;
        $this->name = $name;
    }

    /**
     * @return string Absolute pathname within local filesystem
     */
    public function absolute(): string
    {
        return $this->name ? $this->root . DIRECTORY_SEPARATOR . $this->name : $this->root;
    }

    /**
     * @return string Path name relative to its root directory
     */
    public function relative(): string
    {
        return $this->name;
    }

    protected function filename(string $name): FileName
    {
        $name = $this->name ? $this->name . DIRECTORY_SEPARATOR . $name : $name;
        return new FileName($this->root, $name);
    }
}
