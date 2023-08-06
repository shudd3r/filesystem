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


class UnableToCreate extends IOException
{
    public static function node(Node $node): self
    {
        $message = 'Could not create `%s` %s in `%s`';
        return new self(sprintf($message, basename($node->name()), self::nodeType($node), dirname($node->pathname())));
    }

    public static function directories(Node $node): self
    {
        $message = 'Could not create directories for `%s` %s in `%s`';
        return new self(sprintf($message, $node->name(), self::nodeType($node), self::nodeRoot($node)));
    }

    public static function link(Node $link, Node $target): self
    {
        $message = 'Could not create `%s` link for `%s` %s in `%s`';
        return new self(sprintf($message, $link->name(), $target->name(), self::nodeType($target), $link->pathname()));
    }

    public static function externalLink(Node $link): self
    {
        $message = 'Could not set external filesystem node as `%s` link target in `%s`';
        return new self(sprintf($message, $link->name(), $link->pathname()));
    }

    public static function indirectLink(Node $link): self
    {
        $message = 'Could not set another link as `%s` link target in `%s`';
        return new self(sprintf($message, $link->name(), $link->pathname()));
    }
}
