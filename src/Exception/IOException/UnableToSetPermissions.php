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


class UnableToSetPermissions extends IOException
{
    public static function forNode(Node $node): self
    {
        $message = 'Unable to set permissions for `%s` %s in `%s`';
        return new self(sprintf($message, basename($node->name()), self::nodeType($node), dirname($node->pathname())));
    }
}
