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


class PermissionDenied extends FilesystemException
{
    public static function forNodeRead(Node $node): self
    {
        $message = '%s `%s` is not readable in `%s`';
        return new self(sprintf($message, ucfirst(self::nodeType($node)), $node->name(), $node->pathname()));
    }

    public static function forNodeWrite(Node $node): self
    {
        $message = '%s `%s` is not writable in `%s`';
        return new self(sprintf($message, ucfirst(self::nodeType($node)), $node->name(), $node->pathname()));
    }

    public static function forNodeRemove(Node $node, string $path): self
    {
        $message = '%s `%s` is not removable - write permission required for `%s`';
        return new self(sprintf($message, ucfirst(self::nodeType($node)), $node->name(), $path));
    }

    public static function forRootRemove(Node $node): self
    {
        return new self(sprintf('Root directory `%s` cannot be removed', $node->pathname()));
    }
}
