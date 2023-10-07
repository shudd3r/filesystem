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

use Shudd3r\Filesystem\Exception\IOException;


class LocalFileTest extends LocalFilesystemTests
{
    public function test_exists_for_existing_file_returns_true(): void
    {
        $root = $this->root(['foo' => ['bar' => ['baz.txt' => 'contents']], 'file.lnk' => 'foo/bar/baz.txt']);
        $this->assertTrue($root->file('foo/bar/baz.txt')->exists());
        $this->assertTrue($root->file('file.lnk')->exists());
    }

    public function test_exists_for_not_existing_file_returns_false(): void
    {
        $root = $this->root(['foo' => ['bar' => ['dir' => []]], 'dir.lnk' => 'foo/bar/dir']);
        $this->assertFalse($root->file('foo/bar/baz.txt')->exists());
        $this->assertFalse($root->file('foo/bar/dir')->exists());
        $this->assertFalse($root->file('dir.lnk')->exists());
    }

    public function test_remove_method_deletes_file(): void
    {
        $file = $this->root(['foo' => ['bar.txt' => '']])->file('foo/bar.txt');
        $this->assertFileExists($file->pathname());
        $this->assertTrue($file->exists());
        $file->remove();
        $this->assertFileDoesNotExist($file->pathname());
        $this->assertFalse($file->exists());
    }

    public function test_contents_returns_file_contents(): void
    {
        $file = $this->root(['foo.txt' => 'contents...'])->file('foo.txt');
        $this->assertSame('contents...', file_get_contents($file->pathname()));
        $this->assertSame('contents...', $file->contents());
    }

    public function test_contents_for_not_existing_file_returns_empty_string(): void
    {
        $this->assertEmpty($this->root()->file('not-exists.txt')->contents());
    }

    public function test_write_for_not_existing_file_creates_file_with_given_contents(): void
    {
        $file = $this->root()->file('foo.txt');
        $file->write('Written contents...');
        $this->assertSame('Written contents...', $file->contents());
    }

    public function test_write_for_existing_file_replaces_its_contents(): void
    {
        $file = $this->root(['foo.txt' => 'Old contents...'])->file('foo.txt');
        $file->write('New contents');
        $this->assertSame('New contents', file_get_contents($file->pathname()));
    }

    public function test_writeStream_for_not_existing_file_creates_file_with_given_contents(): void
    {
        $file = $this->root()->file('bar.txt');
        $file->writeStream($this->stream('foo contents...'));
        $this->assertSame('foo contents...', $file->contents());
    }

    public function test_writeStream_for_existing_file_replaces_its_contents(): void
    {
        $file = $this->root(['bar.txt' => 'old contents'])->file('bar.txt');
        $file->writeStream($this->stream('new contents'));
        $this->assertSame('new contents', $file->contents());
    }

    public function test_append_to_not_existing_file_creates_file(): void
    {
        $file = $this->root()->file('file.txt');
        $this->assertFalse($file->exists());
        $file->append('contents...');
        $this->assertTrue($file->exists());
        $this->assertSame('contents...', $file->contents());
    }

    public function test_append_to_existing_file_appends_to_existing_contents(): void
    {
        $file = $this->root(['file.txt' => 'content'])->file('file.txt');
        $file->append('...added');
        $this->assertSame('content...added', $file->contents());
        $file->append(' more');
        $this->assertSame('content...added more', $file->contents());
    }

    public function test_creating_file_with_directory_structure(): void
    {
        $root = $this->root();
        $file = $root->file('foo/bar/baz.txt');
        $this->assertDirectoryDoesNotExist($this->path('foo'));
        $file->write('contents');
        $this->assertFileExists($file->pathname());
        $this->assertDirectoryExists($this->path('foo'));

        $file = $root->file('foo/baz/file.txt');
        $this->assertDirectoryDoesNotExist($this->path('foo/baz'));
        $file->append('...contents');
        $this->assertFileExists($file->pathname());
        $this->assertDirectoryExists($this->path('foo/baz'));
    }

    public function test_copy_duplicates_contents_of_given_file(): void
    {
        $root = $this->root(['foo.txt' => 'Foo contents']);
        $file = $root->file('bar.txt');
        $file->copy($root->file('foo.txt'));
        $this->assertSame('Foo contents', $file->contents());
    }

    public function test_moveTo_moves_file_to_given_directory(): void
    {
        $root   = $this->root(['foo' => ['file.txt' => 'foo contents...']]);
        $file   = $root->file('foo/file.txt');
        $target = $root->file('bar/file.txt');
        $this->assertFileDoesNotExist($target->pathname());
        $file->moveTo($root->subdirectory('bar'));
        $this->assertFileExists($target->pathname());
        $this->assertSame('foo contents...', $target->contents());
        $this->assertFalse($file->exists());
    }

    public function test_moveTo_with_specified_name_moves_file_with_changed_name(): void
    {
        $root   = $this->root(['baz.txt' => 'baz contents...']);
        $file   = $root->file('baz.txt');
        $target = $root->file('foo/bar/moved.file');
        $file->moveTo($root, 'foo/bar/moved.file');
        $this->assertSame('baz contents...', $target->contents());
        $this->assertFalse($file->exists());
    }

    public function test_moveTo_overwrites_existing_file(): void
    {
        $root   = $this->root(['foo' => ['bar' => ['baz.txt' => 'old contents']], 'foo.txt' => 'new contents']);
        $file   = $root->file('foo.txt');
        $target = $root->file('foo/bar/baz.txt');
        $this->assertSame('old contents', $target->contents());
        $file->moveTo($root->subdirectory('foo'), 'bar/baz.txt');
        $this->assertSame('new contents', $target->contents());
        $this->assertFalse($file->exists());
    }

    public function test_moveTo_for_not_existing_file_has_no_effect(): void
    {
        $root   = $this->root(['bar.txt' => 'old contents']);
        $file   = $root->file('foo/bar.txt');
        $target = $root->file('bar.txt');
        $file->moveTo($root);
        $this->assertSame('old contents', $target->contents());
        $this->assertFalse($file->exists());
    }

    public function test_contentStream_for_not_existing_file_returns_null(): void
    {
        $this->assertNull($this->root()->file('foo.txt')->contentStream());
    }

    public function test_contentStream_for_existing_file_returns_streamable_contents(): void
    {
        $file = $this->root(['foo.txt' => 'foo contents...'])->file('foo.txt');
        $this->assertSame($file->contents(), $file->contentStream()->contents());
    }

    public function test_runtime_file_write_failures(): void
    {
        $file  = $this->root()->file('foo/bar/baz.txt');
        $write = fn () => $file->write('something');
        $this->assertIOException(IOException\UnableToCreate::class, $write, 'mkdir');
        $this->assertIOException(IOException\UnableToCreate::class, $write, 'file_put_contents');
        $this->assertIOException(IOException\UnableToSetPermissions::class, $write, 'chmod');
        $this->assertIOException(IOException\UnableToWriteContents::class, $write, 'file_put_contents');
    }

    public function test_runtime_remove_file_failures(): void
    {
        $file   = $this->root(['foo' => ['bar.txt' => 'contents']])->file('foo/bar.txt');
        $remove = fn () => $file->remove();
        $this->assertIOException(IOException\UnableToRemove::class, $remove, 'unlink');
    }

    public function test_runtime_read_file_failures(): void
    {
        $file      = $this->root(['foo' => ['bar.txt' => 'contents']])->file('foo/bar.txt');
        $read      = fn () => $file->contents();
        $exception = IOException\UnableToReadContents::class;
        $this->assertIOException($exception, $read, 'fopen');
        $this->assertIOException($exception, $read, 'flock');
        $this->assertIOException($exception, $read, 'file_get_contents');
    }

    public function test_self_reference_write_is_ignored(): void
    {
        $root = $this->root(['foo.txt' => 'contents']);
        $file = $root->file('foo.txt');

        $file->writeStream($file->contentStream());
        $this->assertSame('contents', $file->contents());

        $file->copy($file);
        $this->assertSame('contents', $file->contents());

        $file->moveTo($root);
        $this->assertSame('contents', $file->contents());
    }
}
