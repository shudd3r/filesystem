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

    public function test_symlinks_are_followed(): void
    {
        $paths = $this->resolvablePaths();
        $this->assertEquals($this->path($paths['file']), $this->path($paths['file symlink']));
        $this->assertEquals($this->path($paths['directory']), $this->path($paths['directory symlink']));
    }

    public function test_casting_to_string_returns_real_path()
    {
        $paths = $this->resolvablePaths();
        $this->assertSame($paths['file'], (string) $this->path($paths['file']));
        $this->assertSame($paths['directory'], (string) $this->path($paths['directory']));
        $this->assertSame($paths['file'], (string) $this->path($paths['file symlink']));
        $this->assertSame($paths['directory'], (string) $this->path($paths['directory symlink']));
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
        chdir(self::$temp->directory('.'));
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

    private function path(string $path): ?Path
    {
        return Path::fromString($path);
    }
}
