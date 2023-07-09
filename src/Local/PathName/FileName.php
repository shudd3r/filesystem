<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Local\PathName;

use Shudd3r\Filesystem\Local\Pathname;


class FileName extends Pathname
{
    private string $name;

    protected function __construct(string $root, string $name)
    {
        parent::__construct($root);
        $this->name = $name;
    }

    public function __toString(): string
    {
        return $this->path . DIRECTORY_SEPARATOR . $this->name;
    }

    public function name(): string
    {
        return $this->name;
    }
}
