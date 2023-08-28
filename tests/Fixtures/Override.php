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


class Override
{
    private static array $functions = [];

    /**
     * @param string $function
     * @param mixed  $argValue
     *
     * @return mixed|null
     */
    public static function call(string $function, $argValue = null)
    {
        if (!array_key_exists($function, self::$functions)) { return null; }
        $override = self::$functions[$function];
        $value    = isset($argValue) && is_array($override) ? $override[$argValue] ?? null : $override;
        return is_callable($value) ? $value() : $value;
    }

    public static function set(string $function, $returnValue, $forArgValue = null): void
    {
        self::$functions[$function] = !isset($forArgValue) ? $returnValue : [$forArgValue => $returnValue];
    }

    public static function remove(string $function): void
    {
        unset(self::$functions[$function]);
    }

    public static function reset(): void
    {
        self::$functions = [];
    }
}
