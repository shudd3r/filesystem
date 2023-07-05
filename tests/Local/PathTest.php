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
use Shudd3r\Filesystem\Local\Path;
use Shudd3r\Filesystem\Tests\Fixtures;


class PathTest extends TestCase
{
    use Fixtures\TempFilesHandling;

    public function test_Instance_with_relative_or_invalid_pathname_returns_null(): void
    {
        foreach ($this->unresolvablePaths() as $type => $path) {
            $this->assertNull($this->path(self::$temp->name($path)), sprintf('For absolute `%s`', $type));
            $this->assertNull($this->path(self::$temp->normalized($path)), sprintf('For relative `%s`', $type));
        }
    }

    public function test_Creating_instance_with_absolute_or_valid_symlink_pathname(): void
    {
        foreach ($this->resolvablePaths() as $type => $path) {
            $this->assertInstanceOf(Path::class, $this->path($path), sprintf('For `%s`', $type));
        }
    }

    public function test_Symlinks_are_followed(): void
    {
        $paths = $this->resolvablePaths();
        $this->assertEquals($this->path($paths['file']), $this->path($paths['file symlink']));
        $this->assertEquals($this->path($paths['directory']), $this->path($paths['directory symlink']));
    }

    public function test_Casting_to_string_returns_real_path()
    {
        $paths = $this->resolvablePaths();
        $this->assertSame($paths['file'], (string) $this->path($paths['file']));
        $this->assertSame($paths['directory'], (string) $this->path($paths['directory']));
        $this->assertSame($paths['file'], (string) $this->path($paths['file symlink']));
        $this->assertSame($paths['directory'], (string) $this->path($paths['directory symlink']));
    }

    public function test_Expanding_directory_path_returns_absolute_path_name(): void
    {
        $this->assertSame(self::$temp->name('foo/bar.dir/baz'), $this->path()->expandedWith('foo/bar.dir/baz'));
        $this->assertSame(self::$temp->name('foo/bar.txt'), $this->path()->expandedWith('foo/bar.txt'));
    }

    public function test_Expanding_file_path_returns_null(): void
    {
        $path = $this->path(self::$temp->file('foo/bar.txt'));
        $this->assertNull($path->expandedWith('baz.txt'));
    }

    public function test_Expanding_with_file_on_path_returns_null(): void
    {
        self::$temp->file('foo/bar.txt');
        $this->assertNull($this->path()->expandedWith('foo/bar.txt/baz'));
    }

    public function test_Expanding_with_directory_link_on_path_returns_absolute_symlink_path(): void
    {
        $directory = self::$temp->directory('foo/bar.dir');
        self::$temp->symlink($directory, 'bar.lnk');
        $this->assertSame(self::$temp->name('bar.lnk/baz'), $this->path()->expandedWith('bar.lnk/baz'));
    }

    public function test_Expanding_with_file_link_on_path_returns_null(): void
    {
        $file = self::$temp->file('foo/bar.txt');
        self::$temp->symlink($file, 'bar.lnk');
        $this->assertNull($this->path()->expandedWith('bar.lnk/baz'));
    }

    private function resolvablePaths(): array
    {
        return [
            'file'              => $file = self::$temp->file('exists/dir/foo.txt'),
            'directory'         => $directory = self::$temp->directory('exists/bar'),
            'file symlink'      => self::$temp->symlink($file, 'foo.link'),
            'directory symlink' => self::$temp->symlink($directory, 'dir/link')
        ];
    }

    private function unresolvablePaths(): array
    {
        chdir(self::$temp->directory());
        self::$temp->file('exists/dir/foo.txt');
        $file = self::$temp->file('linked/file.txt');
        self::$temp->symlink($file, 'invalid-link');
        self::$temp->remove($file);
        self::$temp->symlink(self::$temp->directory('exists/dir'), 'valid-link');

        return [
            'not-file'             => 'not_exists.txt',
            'not-dir'              => 'not/exists',
            'ends with step up'    => 'exists/dir/..',
            'step up to dir'       => 'exists/dir/../dir',
            'invalid symlink'      => 'invalid-link',
            'step up from symlink' => 'valid-link/..'
        ];
    }

    private function path(string $path = null): ?Path
    {
        return Path::fromString($path ?? self::$temp->directory());
    }
}
