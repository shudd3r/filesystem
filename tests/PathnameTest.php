<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Tests;

use Shudd3r\Filesystem\Tests\Local\LocalFilesystemTests;
use Shudd3r\Filesystem\Exception;
use Shudd3r\Filesystem\Pathname;


class PathnameTest extends LocalFilesystemTests
{
    public static function invalidNames(): array
    {
        return [
            'empty name'         => [''],
            'resolved empty'     => ['//\\'],
            'empty segment'      => ['foo/bar//baz.txt'],
            'dot segment'        => ['./foo/bar/baz'],
            'double dot segment' => ['foo/baz/../dir']
        ];
    }

    public static function acceptedNameVariations(): array
    {
        return [
            ['/bar/baz'],
            ['bar/baz'],
            ['\bar\baz'],
            ['\\\\\\bar/baz\\'],
            ['\bar/baz\\']
        ];
    }

    public function test_creating_root_node_instance(): void
    {
        $path     = self::$temp->pathname('existing/directory');
        $pathname = $this->path($path);

        $this->assertSame($path, $pathname->absolute());
    }

    public function test_creating_child_node_instance(): void
    {
        $name     = 'foo/bar/baz.txt';
        $pathname = $this->path()->forChildNode($name);

        $this->assertSame(self::$temp->relative($name), $pathname->relative());
        $this->assertSame(self::$temp->pathname($name), $pathname->absolute());
    }

    /** @dataProvider invalidNames */
    public function test_invalid_child_node_name_throws_Exception(string $name): void
    {
        $this->expectException(Exception\InvalidNodeName::class);
        $this->path()->forChildNode($name);
    }

    public function test_converting_relative_name_to_root_returns_root_directory_name(): void
    {
        $path = self::$temp->directory('foo/bar');
        $this->assertEquals($this->path($path), $newRoot = $this->path()->forChildNode('foo/bar')->asRoot());
        $this->assertSame($newRoot, $newRoot->asRoot());
    }

    /** @dataProvider acceptedNameVariations */
    public function test_child_node_name_separator_normalization(string $name): void
    {
        $this->assertSame(self::$temp->pathname($name), $this->path()->forChildNode($name)->absolute());
        $this->assertSame(self::$temp->relative($name), $this->path()->forChildNode($name)->relative());
    }

    private function path(string $pathname = null): Pathname
    {
        return Pathname::root($pathname ?? self::$temp->directory(), DIRECTORY_SEPARATOR);
    }
}
