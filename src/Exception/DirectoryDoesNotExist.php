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


class DirectoryDoesNotExist extends Exception
{
    public static function forRoot(string $path, string $name): self
    {
        $message = 'Root instance for not existing directory is not allowed. Create `%s` directory in `%s` first';
        return new self(sprintf($message, $name, $path));
    }
}
