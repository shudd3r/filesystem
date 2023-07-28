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

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;
use InvalidArgumentException;
use RuntimeException;


class TempFiles
{
    private string $root;

    public function __construct(string $testName = null)
    {
        $tmpName = getenv('DEV_TESTS_DIRECTORY') . '/' . ($testName ?? 'test' . bin2hex(random_bytes(3)));
        $this->root = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . $this->relative($tmpName);
        is_dir($this->root) || mkdir($this->root, 0700, true);
    }

    public function __destruct()
    {
        $this->clear();
        rmdir($this->root);
    }

    public function clear(): void
    {
        $flags = FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_PATHNAME;
        $nodes = new RecursiveDirectoryIterator($this->root, $flags);
        $nodes = new RecursiveIteratorIterator($nodes, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($nodes as $pathname) {
            $this->remove($pathname);
        }
    }

    public function file(string $filename, string $contents = ''): string
    {
        $this->directory(dirname($filename));
        file_put_contents($filename = $this->pathname($filename), $contents);
        return $filename;
    }

    public function directory(string $directory = '.'): string
    {
        $directory = $directory === '.' ? $this->root : $this->pathname($directory);
        if (!is_dir($directory)) {
            mkdir($directory, 0700, true);
        }
        return $directory;
    }

    public function symlink(string $target, string $name): string
    {
        $invalid = false;
        if (!$target) {
            $invalid = true;
            $target  = $this->file('remove.after.link');
        }

        if (strpos($target, $this->root) !== 0) {
            throw new InvalidArgumentException();
        }

        $this->directory($this->relative(dirname($name)));

        if (!symlink($target, $name = $this->pathname($name))) {
            throw new RuntimeException();
        }

        if ($invalid) { $this->remove($target); }

        return $name;
    }

    public function remove(string $pathname): void
    {
        $isWinOS = DIRECTORY_SEPARATOR === '\\';
        $isFile  = $isWinOS ? is_file($pathname) : is_file($pathname) || is_link($pathname);
        if ($isFile || is_dir($pathname)) {
            $isFile ? unlink($pathname) : rmdir($pathname);
            return;
        }

        @unlink($pathname) || rmdir($pathname);
    }

    public function pathname(string $nodeName): string
    {
        if ($nodeName === '.') { return $this->root; }
        $path = $this->relative($nodeName);
        return $this->root . DIRECTORY_SEPARATOR . $path;
    }

    public function relative(string $path): string
    {
        return trim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
    }
}
