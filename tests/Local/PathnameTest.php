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

use PHPUnit\Framework\TestCase;
use Shudd3r\Filesystem\Local\Pathname;
use Shudd3r\Filesystem\Exception;
use Shudd3r\Filesystem\Tests\Fixtures;


class PathnameTest extends TestCase
{
    use Fixtures\TempFilesHandling;

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
        foreach ($this->invalidInstancePaths() as $type => $path) {
            $this->assertNull($this->path($path), "Failed for `$type`");
        }

        $path = self::$temp->directory('existing/directory');
        $this->assertSame($path, realpath($path));
        $this->assertTrue(is_dir($path));
        $this->assertInstanceOf(Pathname::class, $this->path($path));
    }

    public function test_creating_child_node_instance(): void
    {
        $name = 'foo/bar/baz.txt';
        $this->assertSame(self::$temp->name($name), $this->path()->forChildNode($name)->absolute());
        $this->assertSame(self::$temp->normalized($name), $this->path()->forChildNode($name)->relative());
    }

    /** @dataProvider invalidNames */
    public function test_invalid_child_node_name_throws_Exception(string $name): void
    {
        $this->expectException(Exception\InvalidPath::class);
        $this->path()->forChildNode($name);
    }

    public function test_converting_relative_name_to_root_returns_root_directory_name(): void
    {
        $path = self::$temp->directory('foo/bar');
        $this->assertEquals($this->path($path), $newRoot = $this->path()->forChildNode('foo/bar')->asRoot());
        $this->assertSame($newRoot, $newRoot->asRoot());
    }

    public function test_converting_relative_name_for_not_existing_directory_throws_exception(): void
    {
        $directory = $this->path()->forChildNode('foo/bar');
        $this->expectException(Exception\DirectoryDoesNotExist::class);
        $directory->asRoot();
    }

    public function test_instance_path_normalization(): void
    {
        $rootName = self::$temp->directory();
        $expected = self::$temp->directory('foo/bar');
        $this->assertEquals($expected, $this->path($rootName . '/foo/bar')->absolute());
        $this->assertEquals($expected, $this->path(str_replace('/', '\\', $rootName) . '\foo\bar')->absolute());
        $this->assertEquals($expected, $this->path($rootName . '\foo/bar/')->absolute());
        $this->assertEquals($expected, $this->path($rootName . '/foo\bar\\')->absolute());
    }

    /** @dataProvider acceptedNameVariations */
    public function test_child_node_name_separator_normalization(string $name): void
    {
        $this->assertSame(self::$temp->name($name), $this->path()->forChildNode($name)->absolute());
        $this->assertSame(self::$temp->normalized($name), $this->path()->forChildNode($name)->relative());
    }

    public function test_closestAncestor_method_returns_longest_path_fragment_existing_in_filesystem(): void
    {
        $file      = self::$temp->file('foo/bar/file.txt');
        $directory = self::$temp->directory('foo/bar/dir name');
        $fileLink  = self::$temp->symlink($file, 'linked/file.txt');
        $dirLink   = self::$temp->symlink(dirname($directory), 'linked/dir');
        $linkedDir = self::$temp->name('linked/dir/dir name');
        $deadLink  = self::$temp->symlink('', 'linked/stale');

        $this->assertAncestor($file, 'foo/bar/file.txt/expand/path/file.txt');
        $this->assertAncestor($directory, 'foo/bar/dir name/expanded/sub');
        $this->assertAncestor($fileLink, 'linked/file.txt/as/directory');
        $this->assertAncestor($dirLink, 'linked/dir/not/exist');
        $this->assertAncestor($linkedDir, 'linked/dir/dir name/not/exists');
        $this->assertAncestor($deadLink, 'linked/stale/not/exists');
    }

    private function assertAncestor(string $path, string $nodeName): void
    {
        $node = $this->path()->forChildNode($nodeName);
        $this->assertSame($path, $node->closestAncestor());
    }

    private function invalidInstancePaths(): array
    {
        chdir(self::$temp->directory());
        return [
            'file path'         => self::$temp->file('foo/bar/baz.txt'),
            'not existing path' => self::$temp->name('not/exists'),
            'invalid symlink'   => self::$temp->symlink('', 'link'),
            'valid symlink'     => self::$temp->symlink(self::$temp->name('foo/bar'), 'link'),
            'relative path'     => self::$temp->normalized('./foo/bar'),
            'step-up path'      => self::$temp->name('foo/bar/..'),
            'empty path'        => '',
            'dot path'          => '.'
        ];
    }

    private function path(string $pathname = null): ?Pathname
    {
        return Pathname::root($pathname ?? self::$temp->directory());
    }
}
