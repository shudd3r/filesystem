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


trait TempFilesHandling
{
    private static TempFiles $temp;
    private static string    $cwd;

    public static function setUpBeforeClass(): void
    {
        self::$temp = new TempFiles(basename(static::class));
        self::$cwd  = getcwd();
    }

    public static function tearDownAfterClass(): void
    {
        chdir(self::$cwd);
    }

    protected function tearDown(): void
    {
        self::$temp->clear();
        Override::reset();
    }

    public static function override(string $function, string $pathname, $value): void
    {
        Override::$file[$pathname][$function] = $value;
    }
}
