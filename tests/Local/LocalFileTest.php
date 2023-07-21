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
use Shudd3r\Filesystem\Local\LocalFile;
use Shudd3r\Filesystem\Local\Pathname;
use Shudd3r\Filesystem\Tests\Fixtures;


class LocalFileTest extends TestCase
{
    use Fixtures\TempFilesHandling;

    public function test_pathname_returns_absolute_path_to_file(): void
    {
        $this->assertSame(self::$temp->name('foo/bar/baz.txt'), $this->file('foo/bar/baz.txt')->pathname());
    }

    public function test_name_returns_pathname_relative_to_root_directory(): void
    {
        $this->assertSame(self::$temp->normalized('foo/bar/baz.txt'), $this->file('foo/bar/baz.txt')->name());
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

    public function test_contents_returns_file_contents(): void
    {
        self::$temp->file('foo.txt', 'contents...');
        $this->assertSame('contents...', $this->file('foo.txt')->contents());
    }

    public function test_contents_for_not_existing_file_returns_empty_string(): void
    {
        $this->assertEmpty($this->file('not-exists.txt')->contents());
    }

    public function test_write_for_not_existing_file_creates_file_with_given_contents(): void
    {
        $file = $this->file('foo.txt');
        $this->assertFalse($file->exists());

        $file->write($contents = 'Written contents...');
        $this->assertSame($contents, file_get_contents(self::$temp->name('foo.txt')));
        $this->assertSame($contents, $file->contents());
    }

    public function test_write_for_existing_file_replaces_its_contents(): void
    {
        $filename = self::$temp->file('foo.txt', 'Original contents...');
        $file     = $this->file('foo.txt');
        $this->assertTrue($file->exists());

        $file->write($newContents = 'New contents');
        $this->assertSame($newContents, file_get_contents($filename));
    }

    public function test_append_to_not_existing_file_creates_file(): void
    {
        $file = $this->file('file.txt');
        $this->assertFalse($file->exists());
        $file->append('contents...');
        $this->assertTrue($file->exists());
        $this->assertSame('contents...', $file->contents());
    }

    public function test_append_to_existing_file_appends_to_existing_contents(): void
    {
        self::$temp->file('file.txt');
        $file = $this->file('file.txt');
        $file->append('...added');
        $this->assertSame('...added', $file->contents());
        $file->append(' more');
        $this->assertSame('...added more', $file->contents());
    }

    public function test_creating_file_with_directory_structure(): void
    {
        $file = $this->file('foo/bar/baz.txt');
        $this->assertFalse(is_dir(self::$temp->name('foo')));
        $file->write('');
        $this->assertTrue($file->exists());
        $this->assertTrue(is_dir(self::$temp->name('foo')));

        $file = $this->file('foo/baz/file.txt');
        $this->assertFalse(is_dir(self::$temp->name('foo/baz')));
        $file->append('...contents');
        $this->assertTrue($file->exists());
        $this->assertTrue(is_dir(self::$temp->name('foo/baz')));
    }

    public function test_readable_and_writable_status_for_existing_file(): void
    {
        $filename = self::$temp->file('foo.txt');

        $file = $this->file('foo.txt');
        $this->assertTrue($file->isReadable());
        $this->assertTrue($file->isWritable());
        self::override('is_readable', $filename, false);
        $this->assertFalse($file->isReadable());
        $this->assertTrue($file->isWritable());
        self::override('is_writable', $filename, false);
        $this->assertFalse($file->isReadable());
        $this->assertFalse($file->isWritable());
    }

    public function test_readable_and_writable_status_for_not_existing_files_depends_on_ancestor_permissions(): void
    {
        $directory = self::$temp->directory('foo');

        $file = $this->file('foo/bar/baz.txt');
        $this->assertTrue($file->isReadable());
        $this->assertTrue($file->isWritable());
        self::override('is_readable', $directory, false);
        $this->assertFalse($file->isReadable());
        $this->assertTrue($file->isWritable());
        self::override('is_writable', $directory, false);
        $this->assertFalse($file->isReadable());
        $this->assertFalse($file->isWritable());
    }

    public function test_readable_and_writable_status_for_invalid_file_path_returns_false(): void
    {
        self::$temp->file('foo/file');

        $file = $this->file('foo/file/baz.txt');
        $this->assertFalse($file->isReadable());
        $this->assertFalse($file->isWritable());
    }

    public function test_remove_method(): void
    {
        $path = self::$temp->file('foo/bar.txt');
        $file = $this->file('foo/bar.txt');
        $this->assertFileExists($path);
        $this->assertTrue($file->exists());
        $file->remove();
        $this->assertFileDoesNotExist($path);
        $this->assertFalse($file->exists());
    }

    private function file(string $filename): LocalFile
    {
        return new LocalFile(Pathname::root(self::$temp->directory())->forChildNode($filename));
    }
}
