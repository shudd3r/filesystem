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

use Shudd3r\Filesystem\Local\Pathname;
use Shudd3r\Filesystem\Exception;


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

    public function test_instance_can_only_be_created_with_real_directory_path(): void
    {
        $path = self::$temp->directory('existing/directory');
        $this->assertSame($path, realpath($path));
        $this->assertTrue(is_dir($path));

        $this->assertInstanceOf(Pathname::class, $pathname = $this->path($path));
        $this->assertSame($path, $pathname->root());
        $this->assertSame($path, $pathname->absolute());
    }

    public function test_creating_child_node_instance(): void
    {
        $name     = 'foo/bar/baz.txt';
        $pathname = $this->path()->forChildNode($name);

        $this->assertSame(self::$temp->relative($name), $pathname->relative());
        $this->assertSame($absolute = self::$temp->pathname($name), $pathname->absolute());
        $this->assertSame($absolute, $pathname->root() . DIRECTORY_SEPARATOR . $pathname->relative());
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
        return new Pathname($pathname ?? self::$temp->directory());
    }
}
