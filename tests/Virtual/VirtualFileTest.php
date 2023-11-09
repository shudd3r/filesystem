<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Tests\Virtual;

use Shudd3r\Filesystem\Tests\FileContractTests;
use Shudd3r\Filesystem\Virtual\VirtualDirectory;


class VirtualFileTest extends VirtualFilesystemTests
{
    use FileContractTests;

    public function test_contentStream_returns_null(): void
    {
        $file = $this->root(['foo.txt' => 'contents'])->file('foo.txt');
        $this->assertNull($file->contentStream());
    }

    public function test_moveTo_for_linked_file_moves_link(): void
    {
        $root = $this->root([
            'foo'     => ['foo.txt' => 'foo'],
            'foo.lnk' => '@foo/foo.txt'
        ]);
        $root->file('foo.lnk')->moveTo($root->subdirectory('bar'), 'bar.lnk');
        $this->assertSameStructure($root, [
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

        $targetDir = $root->subdirectory('bar');

        $root->file('foo3.lnk')->moveTo($root->subdirectory('foo'), 'foo.txt');
        $this->assertSameStructure($root, [
            'foo'      => ['foo.txt' => 'foo'],
            'bar'      => ['bar.txt' => 'bar', 'bar.lnk' => '@bar/bar.txt'],
            'foo1.lnk' => '@foo/foo.txt',
            'foo2.lnk' => '@foo/foo.txt'
        ], 'Moved link overwriting its target should be removed');

        $root->file('foo2.lnk')->moveTo($root, 'foo1.lnk');
        $this->assertSameStructure($root, [
            'foo'      => ['foo.txt' => 'foo'],
            'bar'      => ['bar.txt' => 'bar', 'bar.lnk' => '@bar/bar.txt'],
            'foo1.lnk' => '@foo/foo.txt'
        ], 'Moved link with the same file target should be removed');

        $root->file('foo1.lnk')->moveTo($targetDir, 'bar.lnk');
        $this->assertSameStructure($root, [
            'foo' => ['foo.txt' => 'foo'],
            'bar' => ['bar.txt' => 'bar', 'bar.lnk' => '@foo/foo.txt']
        ], 'Link with different file target should overwrite previous target');

        $root->file('bar/bar.lnk')->moveTo($targetDir, 'bar.txt');
        $this->assertSameStructure($root, [
            'foo' => ['foo.txt' => 'foo'],
            'bar' => ['bar.txt' => '@foo/foo.txt']
        ], 'Link should overwrite non-target file');

        $root->file('foo/foo.txt')->moveTo($targetDir, 'bar.txt');
        $this->assertSameStructure($root, [
            'foo' => [],
            'bar' => ['bar.txt' => 'foo']
        ], 'Target file should overwrite link');
    }

    public function test_moveTo_for_external_target_directory(): void
    {
        $root = $this->root(['foo.txt' => 'foo contents']);

        $root->file('foo.txt')->moveTo($targetDir = VirtualDirectory::root());
        $this->assertSame('foo contents', $targetDir->file('foo.txt')->contents());
        $this->assertSameStructure($root, []);
    }
}
