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
use Shudd3r\Filesystem\Node;


class UnexpectedNodeType extends FilesystemException
{
    public static function forNode(Node $node): self
    {
        $type    = self::nodeType($node);
        $found   = $type === 'file' ? 'directory' : 'file';
        $message = 'Requested %s `%s` is a %s (symlink) at `%s`';
        return new self(sprintf($message, $type, $node->name(), $found, $node->pathname()));
    }
}
