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

use Shudd3r\Filesystem\Tests\FileTests;
use Shudd3r\Filesystem\Exception\IOException;


class LocalFileTest extends FileTests
{
    use LocalFilesystemSetup;

    public function test_runtime_file_write_failures(): void
    {
        $file  = $this->root([])->file('foo/bar/baz.txt');
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
        $file = $this->root(['foo' => ['bar.txt' => 'contents']])->file('foo/bar.txt');
        $read = fn () => $file->contents();
        $this->assertIOException(IOException\UnableToReadContents::class, $read, 'fopen');
        $this->assertIOException(IOException\UnableToReadContents::class, $read, 'flock');
        $this->assertIOException(IOException\UnableToReadContents::class, $read, 'file_get_contents');
    }

    public function test_runtime_move_file_failures(): void
    {
        $root = $this->root(['foo.txt' => 'contents']);
        $move = fn () => $root->file('foo.txt')->moveTo($root->directory('foo'));
        $this->assertIOException(IOException\UnableToMove::class, $move, 'rename');
    }
}
