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
        $root = $this->root(['foo' => ['bar.txt' => '']]);
        $root->file('foo/bar.txt')->remove();
        $this->assertSameStructure($root, ['foo' => []]);
    }

    public function test_contents_returns_file_contents(): void
    {
        $file = $this->root(['foo.txt' => 'contents...'])->file('foo.txt');
        $this->assertSame('contents...', $file->contents());
    }

    public function test_contents_for_not_existing_file_returns_empty_string(): void
    {
        $this->assertEmpty($this->root([])->file('not-exists.txt')->contents());
    }

    public function test_write_for_not_existing_file_creates_file_with_given_contents(): void
    {
        $root = $this->root([]);
        $root->file('foo.txt')->write('contents');
        $this->assertSameStructure($root, ['foo.txt' => 'contents']);
    }

    public function test_write_for_existing_file_replaces_its_contents(): void
    {
        $root = $this->root(['foo.txt' => 'old']);
        $root->file('foo.txt')->write('new');
        $this->assertSameStructure($root, ['foo.txt' => 'new']);
    }

    public function test_writeStream_for_not_existing_file_creates_file_with_given_contents(): void
    {
        $root = $this->root([]);
        $root->file('foo.txt')->writeStream($this->stream('foo contents...'));
        $this->assertSameStructure($root, ['foo.txt' => 'foo contents...']);
    }

    public function test_writeStream_for_existing_file_replaces_its_contents(): void
    {
        $root = $this->root(['bar.txt' => 'old contents']);
        $root->file('bar.txt')->writeStream($this->stream('new contents'));
        $this->assertSameStructure($root, ['bar.txt' => 'new contents']);
    }

    public function test_append_to_not_existing_file_creates_file(): void
    {
        $root = $this->root([]);
        $root->file('file.txt')->append('contents...');
        $this->assertSameStructure($root, ['file.txt' => 'contents...']);
    }

    public function test_append_to_existing_file_appends_to_existing_contents(): void
    {
        $root = $this->root(['file.txt' => 'content']);
        $file = $root->file('file.txt');
        $file->append('...added');
        $file->append(' more');
        $this->assertSameStructure($root, ['file.txt' => 'content...added more']);
    }

    public function test_creating_file_with_directory_structure(): void
    {
        $root = $this->root(['foo' => []]);
        $root->file('foo/bar/baz.txt')->write('contents');
        $root->file('baz/dir/file.txt')->append('...contents');
        $this->assertSameStructure($root, [
            'foo' => ['bar' => ['baz.txt' => 'contents']],
            'baz' => ['dir' => ['file.txt' => '...contents']]
        ]);
    }

    public function test_copy_duplicates_contents_of_given_file(): void
    {
        $root = $this->root(['foo.txt' => 'Foo contents']);
        $root->file('bar.txt')->copy($root->file('foo.txt'));
        $this->assertSameStructure($root, ['foo.txt' => 'Foo contents', 'bar.txt' => 'Foo contents']);
    }

    public function test_moveTo_moves_file_to_given_directory(): void
    {
        $root = $this->root(['foo' => ['file.txt' => 'foo contents...']]);
        $root->file('foo/file.txt')->moveTo($root->subdirectory('bar'));
        $this->assertSameStructure($root, ['foo' => [], 'bar' => ['file.txt' => 'foo contents...']]);
    }

    public function test_moveTo_with_specified_name_moves_file_with_changed_name(): void
    {
        $root = $this->root(['baz.txt' => 'baz contents...']);
        $root->file('baz.txt')->moveTo($root, 'foo/bar/moved.file');
        $this->assertSameStructure($root, ['foo' => ['bar' => ['moved.file' => 'baz contents...']]]);
    }

    public function test_moveTo_overwrites_existing_file(): void
    {
        $root = $this->root(['foo' => ['bar' => ['baz.txt' => 'old contents']], 'foo.txt' => 'new contents']);
        $root->file('foo.txt')->moveTo($root->subdirectory('foo'), 'bar/baz.txt');
        $this->assertSameStructure($root, ['foo' => ['bar' => ['baz.txt' => 'new contents']]]);
    }

    public function test_moveTo_for_not_existing_file_is_ignored(): void
    {
        $root = $this->root(['foo' => []]);
        $root->file('bar.txt')->moveTo($root->subdirectory('foo'));
        $this->assertSameStructure($root, ['foo' => []]);
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

    public function test_self_reference_writes_are_ignored(): void
    {
        $initialStructure = [
            'foo' => ['foo.txt' => 'contents'],
            'bar' => ['bar.txt' => '@foo/foo.txt', 'foo.lnk' => '@foo']
        ];

        $root = $this->root($initialStructure);
        $file = $root->file('foo/foo.txt');
        $link = $root->file('bar/bar.txt');

        if ($fileStream = $file->contentStream()) {
            $linkStream = $link->contentStream();
            $file->writeStream($fileStream);
            $link->writeStream($fileStream);
            $link->writeStream($linkStream);
            $file->writeStream($linkStream);
            $this->assertSameStructure($root, $initialStructure);
        }

        $file->copy($file);
        $link->copy($file);
        $file->copy($link);
        $link->copy($link);
        $this->assertSameStructure($root, $initialStructure);

        $fileDirectory = $root->subdirectory('foo');
        $linkDirectory = $root->subdirectory('bar');
        $file->moveTo($fileDirectory);
        $link->moveTo($fileDirectory, 'foo.txt');
        $file->moveTo($linkDirectory, 'bar.txt');
        $link->moveTo($linkDirectory);
        $this->assertSameStructure($root, $initialStructure);

        $fileDirectoryLink = $root->subdirectory('bar/foo.lnk');
        $file->moveTo($fileDirectoryLink);
        $link->moveTo($fileDirectoryLink, 'foo.txt');
        $this->assertSameStructure($root, $initialStructure);
    }
}
