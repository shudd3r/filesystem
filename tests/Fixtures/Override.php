<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Tests\Fixtures;

include_once __DIR__ . '/native-functions.php';


class Override
{
    public static array $file = [];

    public static function reset(): void
    {
        self::$file = [];
    }
}
