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

use Shudd3r\Filesystem\FilesystemException;
use Shudd3r\Filesystem\Directory;


class RootDirectoryNotFound extends FilesystemException
{
    public static function forRoot(Directory $directory): self
    {
        $message = 'Root directory instance requires existing directory path. Create `%s` directory in `%s` first';
        return new self(sprintf($message, $directory->name(), self::nodeRoot($directory)));
    }
}
