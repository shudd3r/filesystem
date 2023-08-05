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
use Shudd3r\Filesystem\Exception\IOException\UnableToCreate;
use Shudd3r\Filesystem\Exception\IOException\UnableToReadContents;
use Shudd3r\Filesystem\Exception\IOException\UnableToRemove;
use Shudd3r\Filesystem\Exception\IOException\UnableToSetPermissions;
use Shudd3r\Filesystem\Exception\IOException\UnableToWriteContents;
use Shudd3r\Filesystem\Generic\ContentStream;
use Shudd3r\Filesystem\Local\LocalFile;
use Shudd3r\Filesystem\Local\Pathname;
use Shudd3r\Filesystem\Tests\Fixtures;

require_once dirname(__DIR__) . '/Fixtures/native-override/local.php';


class LocalFileTest extends TestCase
{
    use Fixtures\TestUtilities;

    public function test_exists_for_existing_file_returns_true(): void
    {
        self::$temp->symlink(self::$temp->file('foo/bar/baz.txt'), 'file.lnk');
        $this->assertTrue($this->file('foo/bar/baz.txt')->exists());
        $this->assertTrue($this->file('file.lnk')->exists());
    }

    public function test_exists_for_not_existing_file_returns_false(): void
    {
        self::$temp->symlink(self::$temp->directory('foo/bar/dir'), 'dir.lnk');
        $this->assertFalse($this->file('foo/bar/baz.txt')->exists());
        $this->assertFalse($this->file('foo/bar/dir')->exists());
        $this->assertFalse($this->file('dir.lnk')->exists());
    }

    public function test_remove_method_deletes_file(): void
    {
        $path = self::$temp->file('foo/bar.txt');
        $file = $this->file('foo/bar.txt');
        $this->assertFileExists($path);
        $this->assertTrue($file->exists());
        $file->remove();
        $this->assertFileDoesNotExist($path);
        $this->assertFalse($file->exists());
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
        $this->assertSame($contents, file_get_contents(self::$temp->pathname('foo.txt')));
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

    public function test_writeStream_for_not_existing_file_creates_file_with_given_contents(): void
    {
        $stream = new ContentStream($this->resource($contents = 'foo contents...'));
        $this->file('bar.txt')->writeStream($stream);
        $this->assertSame($contents, file_get_contents(self::$temp->pathname('bar.txt')));
    }

    public function test_writeStream_for_existing_file_replaces_its_contents(): void
    {
        self::$temp->file('bar.txt', $old = 'old contents');
        $this->assertSame($old, file_get_contents(self::$temp->pathname('bar.txt')));
        $stream = new ContentStream($this->resource($new = 'new contents'));
        $this->file('bar.txt')->writeStream($stream);
        $this->assertSame($new, file_get_contents(self::$temp->pathname('bar.txt')));
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
        self::$temp->file('foo.txt', 'Foo contents');
        $file = $this->file('bar.txt');
        $file->copy($this->file('foo.txt'));
        $this->assertSame('Foo contents', $file->contents());
    }

    public function test_contentStream_for_not_existing_file_returns_null(): void
    {
        $this->assertNull($this->file('foo.txt')->contentStream());
    }

    public function test_contentStream_for_existing_file_returns_streamable_contents(): void
    {
        self::$temp->file('foo.txt', 'foo contents...');
        $file = $this->file('foo.txt');
        $this->assertInstanceOf(ContentStream::class, $stream = $file->contentStream());
        $this->assertSame($file->contents(), fread($stream->resource(), 1024));
    }

    public function test_runtime_file_write_failures(): void
    {
        $file  = $this->file('foo/bar/baz.txt');
        $write = fn () => $file->write('something');

        $this->assertIOException(UnableToCreate::class, $write, 'mkdir');
        $this->assertIOException(UnableToCreate::class, $write, 'file_put_contents');
        $this->assertIOException(UnableToSetPermissions::class, $write, 'chmod');
        $this->assertIOException(UnableToWriteContents::class, $write, 'file_put_contents');
    }

    public function test_runtime_remove_file_failures(): void
    {
        self::$temp->file('foo/bar/baz.txt', 'contents');
        $file   = $this->file('foo/bar/baz.txt');
        $remove = fn () => $file->remove();

        $this->assertIOException(UnableToRemove::class, $remove, 'unlink');
    }

    public function test_runtime_read_file_failures(): void
    {
        self::$temp->file('foo/bar/baz.txt', 'contents');
        $file = $this->file('foo/bar/baz.txt');
        $read = fn () => $file->contents();

        $this->assertIOException(UnableToReadContents::class, $read, 'fopen');
        $this->assertIOException(UnableToReadContents::class, $read, 'flock');
        $this->assertIOException(UnableToReadContents::class, $read, 'file_get_contents');
    }

    private function file(string $filename): LocalFile
    {
        return new LocalFile(Pathname::root(self::$temp->directory())->forChildNode($filename));
    }
}
