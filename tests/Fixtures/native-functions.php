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

function is_readable(string $filename): bool
{
    return Override::call('is_readable', $filename) ?? \is_readable($filename);
}

function is_writable(string $filename): bool
{
    return Override::call('is_writable', $filename) ?? \is_writable($filename);
}

function chmod(string $filename, int $permissions): bool
{
    return Override::call('chmod', $filename) ?? \chmod($filename, $permissions);
}

/**
 * @param mixed $data
 *
 * @return false|int
 */
function file_put_contents(string $filename, $data, int $flags = 0)
{
    return Override::call('file_put_contents', $filename) ?? \file_put_contents($filename, $data, $flags);
}

function mkdir(string $directory, int $permissions = 0777, bool $recursive = false): bool
{
    return Override::call('mkdir', $directory) ?? \mkdir($directory, $permissions, $recursive);
}

function unlink(string $filename): bool
{
    return Override::call('unlink', $filename) ?? \unlink($filename);
}

function rmdir(string $directory): bool
{
    return Override::call('rmdir', $directory) ?? \rmdir($directory);
}

/** @return false|resource */
function fopen(string $filename, string $mode)
{
    return Override::call('fopen', $filename) ?? \fopen($filename, $mode);
}

/** @param resource $stream */
function flock($stream, int $operation): bool
{
    return Override::call('flock', $operation) ?? \flock($stream, $operation);
}

/** @return false|string */
function file_get_contents(string $filename)
{
    return Override::call('file_get_contents', $filename) ?? \file_get_contents($filename);
}

function symlink(string $target, string $link): bool
{
    return Override::call('symlink', $target) ?? \symlink($target, $link);
}

function rename(string $from, string $to): bool
{
    return Override::call('rename', $from) ?? \rename($from, $to);
}
