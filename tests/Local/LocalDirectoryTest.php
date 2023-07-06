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

    public function test_instance_can_only_be_created_with_real_directory_path(): void
    {
        foreach ($this->invalidDirectoryPaths() as $type => $path) {
            $this->assertNull($this->directory($path), "Failed for `$type`");
        }

        $path = self::$temp->directory('existing/directory');
        $this->assertSame($path, realpath($path));
        $this->assertTrue(is_dir($path));
        $this->assertInstanceOf(LocalDirectory::class, $this->directory($path));
    }

    public function test_path_returns_absolute_path_to_existing_directory(): void
    {
        $path = self::$temp->directory('foo/bar/baz');
        $this->assertSame($path, $this->directory($path)->path());
    }

    public function test_filePath_returns_absolute_path_to_existing_file(): void
    {
        $file = self::$temp->file('foo/bar.txt');
        $this->assertSame($file, $this->directory()->filePath('foo/bar.txt'));
    }

    public function test_filePath_returns_absolute_path_to_not_existing_file(): void
    {
        $file = self::$temp->name('foo/bar.txt');
        $this->assertSame($file, $this->directory()->filePath('foo/bar.txt'));
    }

    public function test_filePath_returns_linked_path_for_symlinks(): void
    {
        $file     = self::$temp->file('foo/bar.txt');
        $fileLink = self::$temp->symlink($file, 'link/file.lnk');
        $this->assertSame($fileLink, $this->directory()->filePath('link\file.lnk'));

        $pathLink = self::$temp->symlink(dirname($file), 'path.lnk') . DIRECTORY_SEPARATOR . 'bar.txt';
        $this->assertSame($pathLink, $this->directory()->filePath('path.lnk/bar.txt'));
    }

    public function test_filePath_returns_null_for_non_file_paths(): void
    {
        foreach ($this->invalidRelativePaths(true) as $type => $relativePath) {
            $this->assertNull($this->directory()->filePath($relativePath), "Failed for `$type`");
        }
    }

    public function test_subdirectoryPath_returns_absolute_path_to_existing_directory(): void
    {
        $path = self::$temp->directory('foo/bar/baz');
        $this->assertSame($path, $this->directory()->subdirectoryPath('foo/bar/baz'));
    }

    public function test_subdirectoryPath_returns_absolute_path_to_not_existing_directory(): void
    {
        $path = self::$temp->name('foo/bar/baz');
        $this->assertSame($path, $this->directory()->subdirectoryPath('foo/bar/baz'));
    }

    public function test_subdirectoryPath_returns_linked_path_for_symlinks(): void
    {
        $dir     = self::$temp->directory('foo/bar/baz');
        $dirLink = self::$temp->symlink($dir, 'link/dir.lnk');
        $this->assertSame($dirLink, $this->directory()->subdirectoryPath('link/dir.lnk'));

        $pathLink = self::$temp->symlink(dirname($dir), 'path.lnk') . DIRECTORY_SEPARATOR . 'baz';
        $this->assertSame($pathLink, $this->directory()->subdirectoryPath('path.lnk/baz'));
    }

    public function test_subdirectoryPath_returns_null_for_non_directory_paths(): void
    {
        foreach ($this->invalidRelativePaths(false) as $type => $relativePath) {
            $this->assertNull($this->directory()->subdirectoryPath($relativePath), "Failed for `$type`");
        }
    }

    public function test_path_separator_normalization(): void
    {
        $root     = self::$temp->directory();
        $instance = $this->directory(self::$temp->directory('foo/bar'));
        $this->assertEquals($instance, $this->directory($root . '/foo/bar'));
        $this->assertEquals($instance, $this->directory(str_replace('/', '\\', $root) . '\foo\bar'));
        $this->assertEquals($instance, $this->directory($root . '\foo/bar/'));
        $this->assertEquals($instance, $this->directory($root . '/foo\bar\\'));

        $path     = self::$temp->name('bar/baz');
        $instance = $this->directory($root);
        $this->assertSame($path, $instance->subdirectoryPath('/bar/baz'));
        $this->assertSame($path, $instance->subdirectoryPath('bar/baz/'));
        $this->assertSame($path, $instance->subdirectoryPath('\bar\baz'));
        $this->assertSame($path, $instance->subdirectoryPath('\\\\\\bar/baz\\'));
        $this->assertSame($path, $instance->filePath('/bar/baz'));
        $this->assertSame($path, $instance->filePath('\bar\baz'));
        $this->assertSame($path, $instance->filePath('bar/baz////'));
        $this->assertSame($path, $instance->filePath('\bar/baz\\'));
    }

    private function invalidDirectoryPaths(): array
    {
        chdir(self::$temp->directory());
        return [
            'file path'         => self::$temp->file('foo/bar/baz.txt'),
            'not existing path' => self::$temp->name('not/exists'),
            'invalid symlink'   => self::$temp->symlink('', 'link'),
            'valid symlink'     => self::$temp->symlink(self::$temp->name('foo/bar'), 'link'),
            'relative path'     => self::$temp->normalized('./foo/bar'),
            'step-up path'      => self::$temp->name('foo/bar/..')
        ];
    }

    private function invalidRelativePaths(bool $forFile): array
    {
        $directory = self::$temp->directory('foo/bar');
        $file      = self::$temp->file('foo/bar/baz.txt');
        self::$temp->symlink($file, 'file/name.lnk');
        self::$temp->symlink($directory, 'dir/name.lnk');
        self::$temp->symlink('', 'dead/name.lnk');

        return [
            'file <=> directory'      => $forFile ? 'foo/bar' : 'foo/bar/baz.txt',
            'link file <=> directory' => $forFile ? 'dir/name.lnk' : 'file/name.lnk',
            'file on path'            => 'foo/bar/baz.txt/file.or.dir',
            'file symlink on path'    => 'file/name.lnk/baz',
            'dead symlink'            => 'dead/name.lnk',
            'dead symlink on path'    => 'dead/name.lnk/baz'
        ];
    }

    private function directory(string $path = null): ?LocalDirectory
    {
        return LocalDirectory::instance($path ?? self::$temp->directory());
    }
}
