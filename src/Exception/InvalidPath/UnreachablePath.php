<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Exception\InvalidPath;

use Shudd3r\Filesystem\Exception\InvalidPath;


class UnreachablePath extends InvalidPath
{
    public static function for(string $path, string $collision): self
    {
        return new self(sprintf('Name collision in `%s` path - existing `%s` is not a directory', $path, $collision));
    }
}
