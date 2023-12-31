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
use Shudd3r\Filesystem\Virtual\VirtualDirectory;


abstract class FileTests extends FilesystemTests
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
        $root->assertStructure(['foo' => []]);
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
        $root->assertStructure(['foo.txt' => 'contents']);
    }

    public function test_write_for_existing_file_replaces_its_contents(): void
    {
        $root = $this->root(['foo.txt' => 'old']);
        $root->file('foo.txt')->write('new');
        $root->assertStructure(['foo.txt' => 'new']);
    }

    public function test_contentStream_for_not_existing_file_returns_null(): void
    {
        $this->assertNull($this->root([])->file('foo.txt')->contentStream());
    }

    public function test_contentStream_for_streamable_file_returns_streamable_contents(): void
    {
        $file = $this->root(['foo.txt' => 'foo contents...'])->file('foo.txt');
        $this->assertSame('foo contents...', $file->contentStream()->contents());
    }

    public function test_writeStream_for_not_existing_file_creates_file_with_given_contents(): void
    {
        $root = $this->root([]);
        $root->file('foo.txt')->writeStream($this->stream('foo contents...'));
        $root->assertStructure(['foo.txt' => 'foo contents...']);
    }

    public function test_writeStream_for_existing_file_replaces_its_contents(): void
    {
        $root = $this->root(['bar.txt' => 'old contents']);
        $root->file('bar.txt')->writeStream($this->stream('new contents'));
        $root->assertStructure(['bar.txt' => 'new contents']);
    }

    public function test_append_to_not_existing_file_creates_file(): void
    {
        $root = $this->root([]);
        $root->file('file.txt')->append('contents...');
        $root->assertStructure(['file.txt' => 'contents...']);
    }

    public function test_append_to_existing_file_appends_to_existing_contents(): void
    {
        $root = $this->root(['file.txt' => 'content']);
        $file = $root->file('file.txt');
        $file->append('...added');
        $file->append(' more');
        $root->assertStructure(['file.txt' => 'content...added more']);
    }

    public function test_creating_file_with_directory_structure(): void
    {
        $root = $this->root(['foo' => []]);
        $root->file('foo/bar/baz.txt')->write('contents');
        $root->file('baz/dir/file.txt')->append('...contents');
        $root->assertStructure([
            'foo' => ['bar' => ['baz.txt' => 'contents']],
            'baz' => ['dir' => ['file.txt' => '...contents']]
        ]);
    }

    public function test_copy_duplicates_contents_of_given_file(): void
    {
        $root = $this->root(['foo.txt' => 'Foo contents']);
        $root->file('bar.txt')->copy($root->file('foo.txt'));
        $root->assertStructure(['foo.txt' => 'Foo contents', 'bar.txt' => 'Foo contents']);
    }

    public function test_moveTo_moves_file_to_given_directory(): void
    {
        $root = $this->root(['foo' => ['file.txt' => 'foo contents...']]);
        $root->file('foo/file.txt')->moveTo($root->directory('bar'));
        $root->assertStructure(['foo' => [], 'bar' => ['file.txt' => 'foo contents...']]);
    }

    public function test_moveTo_with_specified_name_moves_file_with_changed_name(): void
    {
        $root = $this->root(['baz.txt' => 'baz contents...']);
        $root->file('baz.txt')->moveTo($root->directory(), 'foo/bar/moved.file');
        $root->assertStructure(['foo' => ['bar' => ['moved.file' => 'baz contents...']]]);
    }

    public function test_moveTo_overwrites_existing_file(): void
    {
        $root = $this->root(['foo' => ['bar' => ['baz.txt' => 'old contents']], 'foo.txt' => 'new contents']);
        $root->file('foo.txt')->moveTo($root->directory('foo'), 'bar/baz.txt');
        $root->assertStructure(['foo' => ['bar' => ['baz.txt' => 'new contents']]]);
    }

    public function test_moveTo_for_not_existing_file_is_ignored(): void
    {
        $root = $this->root(['foo' => []]);
        $root->file('bar.txt')->moveTo($root->directory('foo'));
        $root->assertStructure(['foo' => []]);
    }

    public function test_moveTo_for_linked_file_moves_link(): void
    {
        $root = $this->root([
            'foo'     => ['foo.txt' => 'foo'],
            'foo.lnk' => '@foo/foo.txt'
        ]);
        $root->file('foo.lnk')->moveTo($root->directory('bar'), 'bar.lnk');
        $root->assertStructure([
            'foo' => ['foo.txt' => 'foo'],
            'bar' => ['bar.lnk' => '@foo/foo.txt']
        ]);
    }

    public function test_moveTo_overwrite_for_linked_file(): void
    {
        $root = $this->root([
            'foo'      => ['foo.txt' => 'foo'],
            'bar'      => ['bar.txt' => 'bar', 'bar.lnk' => '@bar/bar.txt'],
            'foo1.lnk' => '@foo/foo.txt',
            'foo2.lnk' => '@foo/foo.txt',
            'foo3.lnk' => '@foo/foo.txt'
        ]);

        $targetDir = $root->directory('bar');

        $root->file('foo3.lnk')->moveTo($root->directory('foo'), 'foo.txt');
        $root->assertStructure([
            'foo'      => ['foo.txt' => 'foo'],
            'bar'      => ['bar.txt' => 'bar', 'bar.lnk' => '@bar/bar.txt'],
            'foo1.lnk' => '@foo/foo.txt',
            'foo2.lnk' => '@foo/foo.txt'
        ], 'Moved link overwriting its target should be removed');

        $root->file('foo2.lnk')->moveTo($root->directory(), 'foo1.lnk');
        $root->assertStructure([
            'foo'      => ['foo.txt' => 'foo'],
            'bar'      => ['bar.txt' => 'bar', 'bar.lnk' => '@bar/bar.txt'],
            'foo1.lnk' => '@foo/foo.txt'
        ], 'Moved link with the same file target should be removed');

        $root->file('foo1.lnk')->moveTo($targetDir, 'bar.lnk');
        $root->assertStructure([
            'foo' => ['foo.txt' => 'foo'],
            'bar' => ['bar.txt' => 'bar', 'bar.lnk' => '@foo/foo.txt']
        ], 'Link with different file target should overwrite previous target');

        $root->file('bar/bar.lnk')->moveTo($targetDir, 'bar.txt');
        $root->assertStructure([
            'foo' => ['foo.txt' => 'foo'],
            'bar' => ['bar.txt' => '@foo/foo.txt']
        ], 'Link should overwrite non-target file');

        $root->file('foo/foo.txt')->moveTo($targetDir, 'bar.txt');
        $root->assertStructure([
            'foo' => [],
            'bar' => ['bar.txt' => 'foo']
        ], 'Target file should overwrite link');
    }

    public function test_moveTo_for_external_target_directory(): void
    {
        $root = $this->root(['foo.txt' => 'foo contents']);

        $root->file('foo.txt')->moveTo($targetDir = VirtualDirectory::root());
        $this->assertSame('foo contents', $targetDir->file('foo.txt')->contents());
        $root->assertStructure([]);
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
            $root->assertStructure($initialStructure);
        }

        $file->copy($file);
        $link->copy($file);
        $file->copy($link);
        $link->copy($link);
        $root->assertStructure($initialStructure);

        $file->moveTo($root->directory('foo'));
        $link->moveTo($root->directory('bar'));
        $root->assertStructure($initialStructure);

        $file->moveTo($root->directory('bar/foo.lnk'));
        $root->assertStructure($initialStructure);
    }
}
