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
    public static array $functions = [];

    public static function call(string $function, $forArgValue = null)
    {
        $value = isset($forArgValue) && is_array(self::$functions[$function] ?? null)
            ? self::$functions[$function][$forArgValue] ?? null
            : self::$functions[$function] ?? null;
        if ($value === null) { return null; }
        return is_callable($value) ? $value() : $value;
    }

    public static function set(string $function, $returnValue, $argValue): void
    {
        $argValue !== null
            ? self::$functions[$function][$argValue] = $returnValue
            : self::$functions[$function] = $returnValue;
    }

    public static function reset(): void
    {
        self::$functions = [];
    }
}
