<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Tests\Local;

use Shudd3r\Filesystem\Tests\DirectoryTests;
use Shudd3r\Filesystem\Local\LocalDirectory;
use Shudd3r\Filesystem\Exception\IOException;


class LocalDirectoryTest extends DirectoryTests
{
    use LocalFilesystemSetup;

    public function test_static_constructor_for_not_real_directory_path_returns_null(): void
    {
        chdir($this->path());
        $invalidRootPaths = [
            'file path'         => self::$temp->file('foo/bar/baz.txt'),
            'not existing path' => self::$temp->pathname('not/exists'),
            'invalid symlink'   => self::$temp->symlink('not/exists', 'link1'),
            'valid symlink'     => self::$temp->symlink('foo/bar', 'link2'),
            'relative path'     => self::$temp->relative('./foo/bar'),
            'step-up path'      => self::$temp->pathname('foo/bar/..'),
            'empty path'        => '',
            'dot path'          => '.'
        ];

        foreach ($invalidRootPaths as $type => $path) {
            $this->assertNull(LocalDirectory::root($path), sprintf('Failed for `%s`', $type));
        }
    }

    public function test_static_constructor_for_existing_directory_path_returns_root_directory_instance(): void
    {
        $this->assertInstanceOf(LocalDirectory::class, $this->root(['foo' => ['bar' => []]])->directory());

        $root = LocalDirectory::root($path = $this->path('foo/bar'));
        $this->assertSame($path, $root->pathname());
        $this->assertSame('', $root->name());
        $this->assertTrue($root->exists());
    }

    public function test_runtime_remove_directory_failures(): void
    {
        $exception = IOException\UnableToRemove::class;
        $directory = $this->root(['foo' => ['bar' => ['baz.txt' => '', 'sub' => []]]])->directory('foo');
        $this->assertIOException($exception, fn () => $directory->remove(), 'rmdir', $this->path('foo/bar/sub'));
        $this->assertIOException($exception, fn () => $directory->remove(), 'rmdir', $this->path('foo'));

        $directory = $this->root(['foo' => ['bar' => ['baz.txt' => '', 'sub' => []]]])->directory('foo');
        $this->assertIOException($exception, fn () => $directory->remove(), 'unlink', $this->path('foo/bar/baz.txt'));
    }

    public function test_runtime_create_directory_failure(): void
    {
        $directory = $this->root([])->directory('foo');
        $create    = fn () => $directory->create();
        $exception = IOException\UnableToCreate::class;
        $this->assertIOException($exception, $create, 'mkdir', $directory->pathname());
    }
}
