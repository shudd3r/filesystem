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

use Shudd3r\Filesystem\Exception as FilesystemException;
use Shudd3r\Filesystem\Node;


class UnexpectedNodeType extends FilesystemException
{
    public static function forNode(Node $node): self
    {
        $type    = self::nodeType($node);
        $found   = $type === 'file' ? 'directory' : 'file';
        $message = 'Requested `%s` %s is a %s (symlink) at `%s`';
        return new self(sprintf($message, $node->name(), $type, $found, $node->pathname()));
    }

    public static function forLink(Node $link, Node $target): self
    {
        $type    = self::nodeType($target);
        $current = $type === 'file' ? 'directory' : 'file';
        $message = 'Cannot change %s node target into %s directly for `%s` link in `%s`';
        return new self(sprintf($message, $current, $type, $link->name(), $link->pathname()));
    }
}
