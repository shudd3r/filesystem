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


class FailedPermissionCheck extends Exception
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

    public static function forRemove(string $name, string $path, bool $isFile): self
    {
        $type    = $isFile ? 'File' : 'Directory';
        $message = '%s `%s` cannot be removed - directory write permission required for `%s`';
        return new self(sprintf($message, $type, $name, $path));
    }

    public static function forRootRemove(string $path): self
    {
        return new self(sprintf('Root directory `%s` cannot be removed', $path));
    }
}
