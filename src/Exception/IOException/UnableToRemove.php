<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Exception\IOException;

use Shudd3r\Filesystem\Exception\IOException;
use Shudd3r\Filesystem\Node;
use Shudd3r\Filesystem\Directory;


class UnableToRemove extends IOException
{
    public static function node(Node $node): self
    {
        $message = 'Could not remove `%s` %s from `%s`';
        return new self(sprintf($message, basename($node->name()), self::nodeType($node), dirname($node->pathname())));
    }

    public static function directoryNode(Directory $directory, string $path): self
    {
        $message  = 'Could not remove `%s` node from `%s` directory in `%s`';
        $relative = substr($path, strlen($directory->pathname()) + 1);
        return new self(sprintf($message, $relative, $directory->name(), dirname($directory->pathname())));
    }
}
