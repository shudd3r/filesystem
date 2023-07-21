<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Local;

use Shudd3r\Filesystem\Tests\Fixtures\Override;

function is_readable(string $pathname): bool
{
    return Override::$file[$pathname]['is_readable'] ?? \is_readable($pathname);
}

function is_writable(string $pathname): bool
{
    return Override::$file[$pathname]['is_writable'] ?? \is_writable($pathname);
}
