<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Tests\Generic;

use PHPUnit\Framework\TestCase;
use Shudd3r\Filesystem\Generic\Pathname;
use Shudd3r\Filesystem\Exception;


class PathnameTest extends TestCase
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

    public function test_root_instance(): void
    {
        $pathname = $this->path($path = '*root \path\ is///not validated///');
        $this->assertSame($path, $pathname->absolute());
        $this->assertSame('', $pathname->relative());
        $this->assertSame($pathname, $pathname->asRoot());
    }

    public function test_creating_child_node_instance(): void
    {
        $pathname = $this->path('scheme://root')->forChildNode('foo/bar/baz.txt');

        $this->assertSame('foo/bar/baz.txt', $pathname->relative());
        $this->assertSame('scheme://root/foo/bar/baz.txt', $pathname->absolute());
    }

    /** @dataProvider invalidNames */
    public function test_invalid_child_node_name_throws_Exception(string $name): void
    {
        $this->expectException(Exception\InvalidNodeName::class);
        $this->path()->forChildNode($name);
    }

    public function test_converting_relative_name_to_root_returns_root_directory_name(): void
    {
        $pathname = $this->path('scheme://root')->forChildNode('foo/bar/baz.txt');
        $this->assertEquals($this->path('scheme://root/foo/bar/baz.txt'), $pathname->asRoot());
    }

    /** @dataProvider acceptedNameVariations */
    public function test_child_node_name_separator_normalization(string $name): void
    {
        $pathname = $this->path('root\\path/foo\\')->forChildNode($name);
        $this->assertSame('root\\path/foo\\/bar/baz', $pathname->absolute());
        $this->assertSame('bar/baz', $pathname->relative());

        $pathname = $this->path('root\path/foo/', '\\')->forChildNode($name);
        $this->assertSame('root\path/foo/\\bar\\baz', $pathname->absolute());
        $this->assertSame('bar\\baz', $pathname->relative());
    }

    public function test_expanding_root_ending_with_separator_does_not_duplicate_separator(): void
    {
        $path = $this->path('/');
        $this->assertSame('/', $path->absolute());
        $this->assertSame('', $path->relative());

        $path = $path->forChildNode('foo/bar');
        $this->assertSame('/foo/bar', $path->absolute());
        $this->assertSame('foo/bar', $path->relative());

        $path = $path->forChildNode('baz');
        $this->assertSame('/foo/bar/baz', $path->absolute());
        $this->assertSame('foo/bar/baz', $path->relative());

        $path = $this->path('vfs://');
        $this->assertSame('vfs://', $path->absolute());
        $this->assertSame('', $path->relative());

        $path = $path->forChildNode('foo/bar');
        $this->assertSame('vfs://foo/bar', $path->absolute());
        $this->assertSame('foo/bar', $path->relative());

        $path = $path->asRoot();
        $this->assertSame('vfs://foo/bar', $path->absolute());
        $this->assertSame('', $path->relative());

        $path = $path->forChildNode('baz');
        $this->assertSame('vfs://foo/bar/baz', $path->absolute());
        $this->assertSame('baz', $path->relative());
    }

    private function path(string $pathname = 'root', string $separator = '/'): Pathname
    {
        return Pathname::root($pathname, $separator);
    }
}
