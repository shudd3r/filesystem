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

use Shudd3r\Filesystem\Virtual\VirtualDirectory;
use Shudd3r\Filesystem\Virtual\VirtualFile;
use Shudd3r\Filesystem\Virtual\VirtualLink;
use Shudd3r\Filesystem\Exception;


class VirtualDirectoryTest extends VirtualFilesystemTests
{
    public function test_subdirectory_for_valid_path_returns_Directory_with_descendant_path(): void
    {
        $subdirectory = $this->directory()->subdirectory('foo/bar');
        $this->assertInstanceOf(VirtualDirectory::class, $subdirectory);
        $this->assertSame('vfs://foo/bar', $subdirectory->pathname());
        $this->assertSame('foo/bar', $subdirectory->name());
        $this->assertSame('vfs://foo/bar/baz', $subdirectory->subdirectory('baz')->pathname());
    }

    public function test_subdirectory_for_invalid_path_throws_Filesystem_Exception(): void
    {
        $procedure = fn () => $this->directory()->subdirectory('foo//bar');
        $this->assertExceptionType(Exception\InvalidNodeName::class, $procedure);
    }

    public function test_file_for_valid_path_returns_File_with_descendant_path(): void
    {
        $file = $this->directory()->file('foo/file.txt');
        $this->assertInstanceOf(VirtualFile::class, $file);
        $this->assertSame('vfs://foo/file.txt', $file->pathname());
        $this->assertSame('foo/file.txt', $file->name());
    }

    public function test_file_for_invalid_path_throws_Filesystem_Exception(): void
    {
        $procedure = fn () => $this->directory()->file('');
        $this->assertExceptionType(Exception\InvalidNodeName::class, $procedure);
    }

    public function test_link_for_valid_path_returns_Link_with_descendant_path(): void
    {
        $link = $this->directory()->link('foo/bar');
        $this->assertInstanceOf(VirtualLink::class, $link);
        $this->assertSame('vfs://foo/bar', $link->pathname());
        $this->assertSame('foo/bar', $link->name());
    }

    public function test_link_for_invalid_path_throws_Filesystem_Exception(): void
    {
        $procedure = fn () => $this->directory()->link('foo/bar/../bar');
        $this->assertExceptionType(Exception\InvalidNodeName::class, $procedure);
    }

    public function test_exists_for_existing_directory_returns_true(): void
    {
        $root = $this->directory();
        $this->assertTrue($root->exists());
        $this->assertTrue($root->subdirectory('foo')->exists());
        $this->assertTrue($root->subdirectory('foo\bar')->exists());
        $this->assertTrue($root->subdirectory('foo\empty')->exists());
        $this->assertTrue($root->subdirectory('dir.lnk')->exists());
    }

    public function test_exists_for_not_existing_directory_returns_false(): void
    {
        $root = $this->directory();
        $this->assertFalse($root->subdirectory('bar')->exists());
        $this->assertFalse($root->subdirectory('foo\bar\baz.txt')->exists());
        $this->assertFalse($root->subdirectory('foo\file.lnk')->exists());
        $this->assertFalse($root->subdirectory('foo\empty\dir')->exists());
        $this->assertFalse($root->subdirectory('inv.lnk')->exists());
    }

    public function test_create_for_existing_directory_is_ignored(): void
    {
        $root = $this->directory('', $this->exampleStructure());
        $root->subdirectory('foo')->create();
        $root->subdirectory('dir.lnk')->create();
        $this->assertEquals($this->directory('', $this->exampleStructure()), $root);
    }

    public function test_create_for_writable_path_creates_directory(): void
    {
        $root = $this->directory();
        $root->subdirectory('bar')->create();
        $root->subdirectory('foo/empty/dir')->create();
        $root->subdirectory('dir.lnk/baz')->create();
        $expected = $this->directory('', $this->exampleStructure([
            'foo' => ['bar' => ['baz' => []], 'empty' => ['dir' => []]],
            'bar' => []
        ]));
        $this->assertEquals($expected, $root);
    }

    public function test_create_for_not_writable_path_throws_exception(): void
    {
        $cases = [
            'File path'       => [Exception\UnexpectedNodeType::class, 'bar.txt'],
            'File link'       => [Exception\UnexpectedNodeType::class, 'foo/file.lnk'],
            'File descendant' => [Exception\UnexpectedLeafNode::class, 'bar.txt/dir'],
            'Link descendant' => [Exception\UnexpectedLeafNode::class, 'foo/file.lnk/dir']
        ];

        $root = $this->directory();
        foreach ($cases as $case => [$exception, $path]) {
            $directory = $root->subdirectory($path);
            $this->assertExceptionType($exception, fn () => $directory->create(), $case);
        }
    }

    public function test_converting_existing_subdirectory_to_root_directory(): void
    {
        $subdirectory = $this->directory('foo/bar');
        $newRoot      = $subdirectory->asRoot();

        $this->assertSame($subdirectory->pathname(), $newRoot->pathname());

        $this->assertSame('foo/bar', $subdirectory->name());
        $this->assertSame('', $newRoot->name());

        $this->assertSame('foo/bar/baz.txt', $subdirectory->file('baz.txt')->name());
        $this->assertSame('baz.txt', $newRoot->file('baz.txt')->name());
    }

    public function test_converting_not_existing_subdirectory_to_root_throws_exception(): void
    {
        $relative = $this->directory('foo/dir');
        $this->expectException(Exception\RootDirectoryNotFound::class);
        $relative->asRoot();
    }

    public function test_files_returns_all_files_iterator(): void
    {
        $fileStructure = $this->exampleStructure(['foo' => ['fizz' => '', 'buzz' => '']]);

        $directory = $this->directory('', $fileStructure);
        $expected  = $this->files(['bar.txt', 'foo/bar/baz.txt', 'foo/buzz', 'foo/fizz']);
        $this->assertFiles($expected, $directory->files());

        $directory = $directory->subdirectory('foo');
        $expected  = $this->files(['foo/bar/baz.txt', 'foo/buzz', 'foo/fizz']);
        $this->assertFiles($expected, $directory->files());

        $directory = $directory->asRoot();
        $expected  = $this->files(['bar/baz.txt', 'buzz', 'fizz'], 'vfs://foo/');
        $this->assertFiles($expected, $directory->files());
    }

    public function test_files_will_iterate_over_currently_existing_files(): void
    {
        $fileStructure = $this->exampleStructure(['foo' => ['fizz' => '', 'buzz' => '']]);

        $directory = $this->directory('', $fileStructure);
        $files     = $directory->files();
        $directory->subdirectory('foo/bar')->remove();
        $directory->file('bar.txt')->remove();
        $this->assertFiles($this->files(['foo/buzz', 'foo/fizz']), $files);
    }

    public function test_remove_method_deletes_existing_structure(): void
    {
        $root = $this->directory();
        $root->subdirectory('foo')->remove();
        $expected = $this->directory('', $this->exampleStructure(['foo' => null]));
        $this->assertEquals($expected, $root);
    }

    public function test_remove_method_for_not_existing_directory_is_ignored(): void
    {
        $root = $this->directory();
        $root->subdirectory('bar.txt')->remove();
        $root->subdirectory('foo/empty/bar')->remove();
        $this->assertEquals($this->directory(), $root);
    }

    private function files(array $filenames, string $root = 'vfs://'): array
    {
        $files = [];
        foreach ($filenames as $name) {
            $files[$name] = $root . $name;
        }
        return $files;
    }
}
