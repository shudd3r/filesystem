<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Tests;

use Shudd3r\Filesystem\Exception;


trait FileContractTests
{
    public function test_exists_for_existing_file_returns_true(): void
    {
        $root = $this->root(['foo' => ['bar' => ['baz.txt' => 'contents']], 'file.lnk' => '@foo/bar/baz.txt']);
        $this->assertTrue($root->file('foo/bar/baz.txt')->exists());
        $this->assertTrue($root->file('file.lnk')->exists());
    }

    public function test_exists_for_not_existing_file_returns_false(): void
    {
        $root = $this->root(['foo' => ['bar' => ['dir' => []]], 'dir.lnk' => '@foo/bar/dir']);
        $this->assertFalse($root->file('foo/bar/baz.txt')->exists());
        $this->assertFalse($root->file('foo/bar/dir')->exists());
        $this->assertFalse($root->file('dir.lnk')->exists());
    }

    public function test_remove_method_deletes_file(): void
    {
        $file = $this->root(['foo' => ['bar.txt' => '']])->file('foo/bar.txt');
        $file->remove();
        $this->assertFalse($file->exists());
    }

    public function test_contents_returns_file_contents(): void
    {
        $file = $this->root(['foo.txt' => 'contents...'])->file('foo.txt');
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
        $this->assertSame('New contents', $file->contents());
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
        $root = $this->root([]);
        $root->file('foo/bar/baz.txt')->write('contents');
        $root->file('foo/baz/file.txt')->append('...contents');
        $expected = ['foo' => ['bar' => ['baz.txt' => 'contents'], 'baz' => ['file.txt' => '...contents']]];
        $this->assertSameStructure($root, $expected);
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
        $file->moveTo($root->subdirectory('bar'));
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

    public function test_method_calls_on_invalid_file_path_throw_exception(): void
    {
        $root   = $this->root(['foo' => ['file.txt' => ''], 'bar.txt' => '']);
        $stream = $this->stream('contents');
        $copied = $root->file('bar.txt');

        $file      = $root->file('foo/file.txt/bar.txt');
        $exception = Exception\UnexpectedLeafNode::class;
        $this->assertExceptionType($exception, fn () => $file->contents(), 'contents');
        $this->assertExceptionType($exception, fn () => $file->write('contents'), 'write');
        $this->assertExceptionType($exception, fn () => $file->append('contents'), 'append');
        $this->assertExceptionType($exception, fn () => $file->writeStream($stream), 'writeStream');
        $this->assertExceptionType($exception, fn () => $file->copy($copied), 'copy');

        $file      = $root->file('foo');
        $exception = Exception\UnexpectedNodeType::class;
        $this->assertExceptionType($exception, fn () => $file->contents(), 'contents');
        $this->assertExceptionType($exception, fn () => $file->write('contents'), 'write');
        $this->assertExceptionType($exception, fn () => $file->append('contents'), 'append');
        $this->assertExceptionType($exception, fn () => $file->writeStream($stream), 'writeStream');
        $this->assertExceptionType($exception, fn () => $file->copy($copied), 'copy');
    }
}
