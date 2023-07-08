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
use Shudd3r\Filesystem\Local\LocalFile;
use Shudd3r\Filesystem\Tests\Fixtures;


class LocalFileTest extends TestCase
{
    use Fixtures\TempFilesHandling;

    public function test_pathname_returns_absolute_path_to_file(): void
    {
        $expected = self::$temp->directory() . DIRECTORY_SEPARATOR . 'foo/bar/baz.txt';
        $this->assertSame($expected, $this->file('foo/bar/baz.txt')->pathname());
    }

    public function test_name_returns_pathname_relative_to_root_directory(): void
    {
        $this->assertSame('foo/bar/baz.txt', $this->file('foo/bar/baz.txt')->name());
    }

    public function test_exists_for_existing_file_returns_true(): void
    {
        self::$temp->symlink(self::$temp->file('foo/bar/baz.txt'), 'file.lnk');
        $this->assertTrue($this->file('foo/bar/baz.txt')->exists());
        $this->assertTrue($this->file('file.lnk')->exists());
    }

    public function test_exists_for_not_existing_file_returns_false(): void
    {
        $this->assertFalse($this->file('foo/bar/baz.txt')->exists());
    }

    public function test_exists_for_non_file_node_returns_false(): void
    {
        self::$temp->symlink(self::$temp->directory('foo/bar.dir'), 'directory.lnk');
        $this->assertFalse($this->file('foo/bar.dir')->exists());
        $this->assertFalse($this->file('directory.lnk')->exists());
    }

    public function test_contents_returns_file_contents(): void
    {
        self::$temp->file('foo.txt', 'contents...');
        $this->assertSame('contents...', $this->file('foo.txt')->contents());
    }

    public function test_contents_for_not_existing_file_returns_empty_string(): void
    {
        $this->assertEmpty($this->file('not-exists.txt')->contents());
    }

    private function file(string $filename): LocalFile
    {
        return new LocalFile(LocalDirectory::instance(self::$temp->directory()), $filename);
    }
}
