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

use Shudd3r\Filesystem\FilesystemException;


trait ExceptionAssertions
{
    private function assertIOException(string $exception, callable $procedure, string $override, $argValue = null): void
    {
        $this->override($override, function () {
            trigger_error('emulated warning', E_USER_WARNING);
            return false;
        }, $argValue);
        $this->assertExceptionType($exception, $procedure);
        $this->override($override, null);
    }

    private function assertExceptionType(string $expected, callable $procedure, string $case = ''): void
    {
        $title = $case ? 'Case "' . $case . '": ' : '';
        try {
            $procedure();
        } catch (FilesystemException $ex) {
            $message = $title . 'Unexpected Exception type - expected `%s` caught `%s`';
            $this->assertInstanceOf($expected, $ex, sprintf($message, $expected, get_class($ex)));
            return;
        }

        $this->fail(sprintf($title . 'No Exception thrown - expected `%s`', $expected));
    }
}