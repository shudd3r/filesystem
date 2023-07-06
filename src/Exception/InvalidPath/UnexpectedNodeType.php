<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Exception\InvalidPath;

use Shudd3r\Filesystem\Exception\InvalidPath;


class UnexpectedNodeType extends InvalidPath
{
    public static function for(string $path, bool $isFile = false): self
    {
        $type      = $isFile ? 'file/invalid symlink' : 'directory/invalid symlink';
        $requested = $isFile ? 'directory' : 'file';
        return new self(sprintf('Requested %s `%s` is a %s', $requested, $path, $type));
    }
}
