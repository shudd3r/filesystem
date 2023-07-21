<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Exception;

use Shudd3r\Filesystem\Exception;


class AccessDenied extends Exception
{
    public static function forRead(string $name, string $path, bool $isFile): self
    {
        $type = $isFile ? 'File' : 'Directory';
        return new self(sprintf('%s `%s` is not readable in `%s`', $type, $name, $path));
    }

    public static function forWrite(string $name, string $path, bool $isFile): self
    {
        $type = $isFile ? 'File' : 'Directory';
        return new self(sprintf('%s `%s` is not writable in `%s`', $type, $name, $path));
    }
}
