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


/**
 * This exception should be handled in concurrent environments where
 * directories, files or symlinks are not controlled by single process
 * (possible race conditions).
 */
class UnreachablePath extends Exception
{
    public static function for(string $path, string $collision, bool $expectedFile = false): self
    {
        if (str_ends_with(DIRECTORY_SEPARATOR . $path, $collision)) {
            $requested = $expectedFile ? 'file' : 'directory';
            $type      = $expectedFile ? 'directory' : 'file';
            return new self(sprintf('Requested %s `%s` is a %s (or invalid symlink)', $requested, $path, $type));
        }

        $message = 'Name collision for requested path `%s` (non directory node at `%s`)';
        return new self(sprintf($message, $path, $collision));
    }
}
