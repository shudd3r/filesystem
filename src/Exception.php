<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem;

use Exception as BaseException;


class Exception extends BaseException
{
    protected static function nodeType(Node $node): string
    {
        return $node instanceof File ? 'file' : 'directory';
    }

    protected static function nodeRoot(Node $node): string
    {
        $nameLength = strlen($node->name()) + 1;
        return substr($node->pathname(), 0, -$nameLength);
    }
}
