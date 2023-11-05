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


class UnexpectedLeafNode extends FilesystemException
{
    public static function forNode(Node $node, string $collision): self
    {
        $message = 'Name collision for `%s` %s path - non directory node at `%s`';
        return new self(sprintf($message, $node->name(), self::nodeType($node), $collision));
    }
}
