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
use Shudd3r\Filesystem\Local\LocalDirectory;
use Shudd3r\Filesystem\Tests\Fixtures;


class LocalDirectoryTest extends TestCase
{
    use Fixtures\TempFilesHandling;

    public function test_Instance_can_only_be_created_with_real_directory_path(): void
    {
        foreach ($this->invalidDirectoryPaths() as $type => $path) {
            $this->assertNull($this->directory($path), "Failed for `$type`");
        }

        $path = self::$temp->directory('existing/directory');
        $this->assertSame($path, realpath($path));
        $this->assertTrue(is_dir($path));
        $this->assertInstanceOf(LocalDirectory::class, $this->directory($path));
    }

    public function test_Path_method_returns_instance_path(): void
    {
        $path = self::$temp->directory('foo/bar/baz');
        $this->assertSame($path, $this->directory($path)->path());
    }

    public function test_Expanding_directory_path_returns_absolute_path_name(): void
    {
        $this->assertSame(self::$temp->name('foo/bar.dir/baz'), $this->directory()->expandedWith('foo/bar.dir/baz'));
        $this->assertSame(self::$temp->name('foo/bar.txt'), $this->directory()->expandedWith('foo/bar.txt'));
    }

    public function test_Expanding_with_file_on_path_returns_null(): void
    {
        self::$temp->file('foo/bar.txt');
        $this->assertNull($this->directory()->expandedWith('foo/bar.txt/baz'));
    }

    public function test_Expanding_with_directory_link_on_path_returns_absolute_symlink_path(): void
    {
        $directory = self::$temp->directory('foo/bar.dir');
        self::$temp->symlink($directory, 'bar.lnk');
        $this->assertSame(self::$temp->name('bar.lnk/baz'), $this->directory()->expandedWith('bar.lnk/baz'));
    }

    public function test_Expanding_with_file_link_on_path_returns_null(): void
    {
        $file = self::$temp->file('foo/bar.txt');
        self::$temp->symlink($file, 'bar.lnk');
        $this->assertNull($this->directory()->expandedWith('bar.lnk/baz'));
    }

    private function invalidDirectoryPaths(): array
    {
        chdir(self::$temp->directory());
        return [
            'file path'         => self::$temp->file('foo/bar/baz.txt'),
            'not existing path' => self::$temp->name('not/exists'),
            'invalid symlink'   => self::$temp->symlink('', 'link'),
            'valid symlink'     => self::$temp->symlink(self::$temp->name('foo/bar'), 'link'),
            'relative path'     => self::$temp->normalized('foo/bar'),
            'step-up path'      => self::$temp->name('foo/bar/..')
        ];
    }

    private function directory(string $path = null): ?LocalDirectory
    {
        return LocalDirectory::instance($path ?? self::$temp->directory());
    }
}
