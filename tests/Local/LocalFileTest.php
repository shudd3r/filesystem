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

use Shudd3r\Filesystem\Tests\FileContractTests;
use Shudd3r\Filesystem\Exception;


class LocalFileTest extends LocalFilesystemTests
{
    use FileContractTests;

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
        $file  = $this->root([])->file('foo/bar/baz.txt');
        $write = fn () => $file->write('something');
        $this->assertIOException(Exception\IOException\UnableToCreate::class, $write, 'mkdir');
        $this->assertIOException(Exception\IOException\UnableToCreate::class, $write, 'file_put_contents');
        $this->assertIOException(Exception\IOException\UnableToSetPermissions::class, $write, 'chmod');
        $this->assertIOException(Exception\IOException\UnableToWriteContents::class, $write, 'file_put_contents');
    }

    public function test_runtime_remove_file_failures(): void
    {
        $file   = $this->root(['foo' => ['bar.txt' => 'contents']])->file('foo/bar.txt');
        $remove = fn () => $file->remove();
        $this->assertIOException(Exception\IOException\UnableToRemove::class, $remove, 'unlink');
    }

    public function test_runtime_read_file_failures(): void
    {
        $file      = $this->root(['foo' => ['bar.txt' => 'contents']])->file('foo/bar.txt');
        $read      = fn () => $file->contents();
        $exception = Exception\IOException\UnableToReadContents::class;
        $this->assertIOException($exception, $read, 'fopen');
        $this->assertIOException($exception, $read, 'flock');
        $this->assertIOException($exception, $read, 'file_get_contents');
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
