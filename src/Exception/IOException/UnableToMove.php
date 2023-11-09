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

use Shudd3r\Filesystem\Directory;
use Shudd3r\Filesystem\Exception\IOException;
use Shudd3r\Filesystem\Node;


class UnableToMove extends IOException
{
    public static function node(Node $node, Directory $target): self
    {
        $message  = 'Could not move `%s` %s from `%s` to `%s`';
        $basename = basename($node->name());
        return new self(sprintf($message, $basename, self::nodeType($node), $node->pathname(), $target->pathname()));
    }
}
