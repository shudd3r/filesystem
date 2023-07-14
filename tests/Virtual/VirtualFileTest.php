<?php

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Tests\Virtual;

use Shudd3r\Filesystem\Virtual\VirtualFile;
use PHPUnit\Framework\TestCase;


class VirtualFileTest extends TestCase
{
    public function test_name_returns_instance_value(): void
    {
        $this->assertSame('foo/bar/baz.txt', $this->file('foo/bar/baz.txt')->name());
    }

    public function test_pathname_is_prefixed_name(): void
    {
        $name = 'foo/bar/baz.txt';
        $this->assertSame('virtual://' . $name, $this->file($name)->pathname());
    }

    public function test_contents_for_file_without_contents_returns_empty_string(): void
    {
        $this->assertSame('', $this->file('file.txt')->contents());
        $this->assertSame('', $this->file('file.txt', '')->contents());
    }

    public function test_write_changes_file_contents(): void
    {
        $file = $this->file('file.txt');
        $file->write('contents...');
        $this->assertSame('contents...', $file->contents());
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
        $file = $this->file('file.txt', '');
        $file->append('...added');
        $this->assertSame('...added', $file->contents());
        $file->append(' more');
        $this->assertSame('...added more', $file->contents());
    }

    public function test_exists_for_instance_with_null_contents_returns_false(): void
    {
        $file = $this->file('file/foo.txt');
        $this->assertFalse($file->exists());
        $file->write('contents');
        $this->assertTrue($file->exists());
    }

    private function file(string $name, ?string $contents = null): VirtualFile
    {
        return new VirtualFile($name, $contents);
    }
}
