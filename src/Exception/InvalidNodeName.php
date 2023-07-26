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


class InvalidNodeName extends FilesystemException
{
    public static function forEmptyName(): self
    {
        return new self('Empty name for child node given');
    }

    public static function forEmptySegment(string $name): self
    {
        return new self(sprintf('Empty path segments not allowed - `%s` given', $name));
    }

    public static function forDotSegment(string $name): self
    {
        return new self(sprintf('Dot segments not allowed - `%s` given', $name));
    }
}
