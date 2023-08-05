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
use Shudd3r\Filesystem\Generic\ContentStream;
use Shudd3r\Filesystem\Local\LocalDirectory;
use Shudd3r\Filesystem\Exception\IOException;
use Shudd3r\Filesystem\Tests\Fixtures;

require_once dirname(__DIR__) . '/Fixtures/native-override/local.php';


class LocalFileTest extends TestCase
{
    use Fixtures\TestUtilities;

    public function test_exists_for_existing_file_returns_true(): void
    {
        $file = $this->file('foo/bar/baz.txt', 'contents');
        $this->assertTrue($file->exists());
        self::$temp->symlink($file->pathname(), 'file.lnk');
        $this->assertTrue($this->file('file.lnk')->exists());
    }

    public function test_exists_for_not_existing_file_returns_false(): void
    {
        $this->assertFalse($this->file('foo/bar/baz.txt')->exists());
        $directory = self::$temp->directory('foo/bar/dir');
        $this->assertFalse($this->file('foo/bar/dir')->exists());
        self::$temp->symlink($directory, 'dir.lnk');
        $this->assertFalse($this->file('dir.lnk')->exists());
    }

    public function test_remove_method_deletes_file(): void
    {
        $file = $this->file('foo/bar.txt', '');
        $this->assertFileExists($file->pathname());
        $this->assertTrue($file->exists());
        $file->remove();
        $this->assertFileDoesNotExist($file->pathname());
        $this->assertFalse($file->exists());
    }

    public function test_contents_returns_file_contents(): void
    {
        $file = $this->file('foo.txt', $contents = 'contents...');
        $this->assertSame($contents, $file->contents());
    }

    public function test_contents_for_not_existing_file_returns_empty_string(): void
    {
        $this->assertEmpty($this->file('not-exists.txt')->contents());
    }

    public function test_write_for_not_existing_file_creates_file_with_given_contents(): void
    {
        $file = $this->file('foo.txt');
        $file->write($contents = 'Written contents...');
        $this->assertSame($contents, file_get_contents($file->pathname()));
        $this->assertSame($contents, $file->contents());
    }

    public function test_write_for_existing_file_replaces_its_contents(): void
    {
        $file = $this->file('foo.txt', 'Old contents...');
        $file->write($newContents = 'New contents');
        $this->assertSame($newContents, file_get_contents($file->pathname()));
    }

    public function test_writeStream_for_not_existing_file_creates_file_with_given_contents(): void
    {
        $stream = new ContentStream($this->resource($contents = 'foo contents...'));
        $this->file('bar.txt')->writeStream($stream);
        $this->assertSame($contents, file_get_contents(self::$temp->pathname('bar.txt')));
    }

    public function test_writeStream_for_existing_file_replaces_its_contents(): void
    {
        $stream = new ContentStream($this->resource($newContents = 'new contents'));
        $this->file('bar.txt', 'old contents')->writeStream($stream);
        $this->assertSame($newContents, file_get_contents(self::$temp->pathname('bar.txt')));
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
        $file = $this->file('file.txt', 'content');
        $file->append('...added');
        $this->assertSame('content...added', $file->contents());
        $file->append(' more');
        $this->assertSame('content...added more', $file->contents());
    }

    public function test_creating_file_with_directory_structure(): void
    {
        $file = $this->file('foo/bar/baz.txt');
        $this->assertFalse(is_dir(self::$temp->pathname('foo')));
        $file->write('');
        $this->assertTrue($file->exists());
        $this->assertTrue(is_dir(self::$temp->pathname('foo')));

        $file = $this->file('foo/baz/file.txt');
        $this->assertFalse(is_dir(self::$temp->pathname('foo/baz')));
        $file->append('...contents');
        $this->assertTrue($file->exists());
        $this->assertTrue(is_dir(self::$temp->pathname('foo/baz')));
    }

    public function test_copy_duplicates_contents_of_given_file(): void
    {
        $file = $this->file('bar.txt');
        $file->copy($this->file('foo.txt', $contents = 'Foo contents'));
        $this->assertSame($contents, $file->contents());
    }

    public function test_moveTo_moves_file_to_given_directory(): void
    {
        $target = self::$temp->pathname('bar/file.txt');
        $file   = $this->file('foo/file.txt', $contents = 'foo contents...');
        $this->assertFileDoesNotExist($target);

        $file->moveTo($this->directory()->subdirectory('bar'));
        $this->assertFileExists($target);
        $this->assertSame($contents, file_get_contents($target));
        $this->assertFalse($file->exists());
    }

    public function test_moveTo_with_specified_name_moves_file_with_changed_name(): void
    {
        $target = self::$temp->pathname('foo/bar/baz.file');
        $file   = $this->file('baz.txt', $contents = 'baz contents...');

        $file->moveTo($this->directory(), 'foo/bar/baz.file');
        $this->assertSame($contents, file_get_contents($target));
        $this->assertFalse($file->exists());
    }

    public function test_moveTo_overwrites_existing_file(): void
    {
        $target = self::$temp->file('foo/bar/baz.txt', 'old contents');
        $file   = $this->file('foo.txt', $newContents = 'new contents');

        $file->moveTo($this->directory(), 'foo/bar/baz.txt');
        $this->assertSame($newContents, file_get_contents($target));
        $this->assertFalse($file->exists());
    }

    public function test_moveTo_for_not_existing_file_has_no_effect(): void
    {
        $target = self::$temp->file('bar.txt', $oldContents = 'old contents');
        $file   = $this->file('foo/bar.txt');

        $file->moveTo($this->directory());
        $this->assertSame($oldContents, file_get_contents($target));
        $this->assertFalse($file->exists());
    }

    public function test_contentStream_for_not_existing_file_returns_null(): void
    {
        $this->assertNull($this->file('foo.txt')->contentStream());
    }

    public function test_contentStream_for_existing_file_returns_streamable_contents(): void
    {
        $file = $this->file('foo.txt', 'foo contents...');
        $this->assertInstanceOf(ContentStream::class, $stream = $file->contentStream());
        $this->assertSame($file->contents(), fread($stream->resource(), 1024));
    }

    public function test_runtime_file_write_failures(): void
    {
        $file  = $this->file('foo/bar/baz.txt');
        $write = fn () => $file->write('something');

        $this->assertIOException(IOException\UnableToCreate::class, $write, 'mkdir');
        $this->assertIOException(IOException\UnableToCreate::class, $write, 'file_put_contents');
        $this->assertIOException(IOException\UnableToSetPermissions::class, $write, 'chmod');
        $this->assertIOException(IOException\UnableToWriteContents::class, $write, 'file_put_contents');
    }

    public function test_runtime_remove_file_failures(): void
    {
        $file   = $this->file('foo/bar/baz.txt', 'contents');
        $remove = fn () => $file->remove();

        $this->assertIOException(IOException\UnableToRemove::class, $remove, 'unlink');
    }

    public function test_runtime_read_file_failures(): void
    {
        $file = $this->file('foo/bar/baz.txt', 'contents');
        $read = fn () => $file->contents();

        $this->assertIOException(IOException\UnableToReadContents::class, $read, 'fopen');
        $this->assertIOException(IOException\UnableToReadContents::class, $read, 'flock');
        $this->assertIOException(IOException\UnableToReadContents::class, $read, 'file_get_contents');
    }

    private function file(string $filename, string $contents = null): LocalFile
    {
        if (isset($contents)) { self::$temp->file($filename, $contents); }
        return new LocalFile(Pathname::root(self::$temp->directory())->forChildNode($filename));
    }

    private function directory(): LocalDirectory
    {
        return new LocalDirectory(Pathname::root(self::$temp->directory()));
    }
}
