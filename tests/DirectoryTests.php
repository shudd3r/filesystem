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


abstract class DirectoryTests extends FilesystemTests
{
    public function test_subdirectory_for_valid_path_returns_Directory_with_descendant_path(): void
    {
        $subdirectory = $this->root([])->directory()->subdirectory('foo/bar');
        $this->assertSame($this->path('foo/bar'), $subdirectory->pathname());
        $this->assertSame('foo/bar', $subdirectory->name());
        $this->assertSame($this->path('foo/bar/baz'), $subdirectory->subdirectory('baz')->pathname());
    }

    public function test_subdirectory_for_invalid_path_throws_Filesystem_Exception(): void
    {
        $directory = $this->root([])->directory();
        $this->assertExceptionType(Exception\InvalidNodeName::class, fn () => $directory->subdirectory('foo//bar'));
    }

    public function test_file_for_valid_path_returns_File_with_descendant_path(): void
    {
        $file = $this->root([])->directory()->file('foo/file.txt');
        $this->assertSame($this->path('foo/file.txt'), $file->pathname());
        $this->assertSame('foo/file.txt', $file->name());
    }

    public function test_file_for_invalid_path_throws_Filesystem_Exception(): void
    {
        $directory = $this->root([])->directory();
        $this->assertExceptionType(Exception\InvalidNodeName::class, fn () => $directory->file(''));
    }

    public function test_link_for_valid_path_returns_Link_with_descendant_path(): void
    {
        $link = $this->root([])->directory()->link('foo/bar');
        $this->assertSame($this->path('foo/bar'), $link->pathname());
        $this->assertSame('foo/bar', $link->name());
    }

    public function test_link_for_invalid_path_throws_Filesystem_Exception(): void
    {
        $directory = $this->root([])->directory();
        $this->assertExceptionType(Exception\InvalidNodeName::class, fn () => $directory->link('foo/bar/../bar'));
    }

    public function test_exists_for_existing_directory_returns_true(): void
    {
        $root = $this->root()->directory();
        $this->assertTrue($root->exists());
        $this->assertTrue($root->subdirectory('foo')->exists());
        $this->assertTrue($root->subdirectory('foo\bar')->exists());
        $this->assertTrue($root->subdirectory('foo\empty')->exists());
        $this->assertTrue($root->subdirectory('dir.lnk')->exists());
    }

    public function test_exists_for_not_existing_directory_returns_false(): void
    {
        $root = $this->root()->directory();
        $this->assertFalse($root->subdirectory('bar')->exists());
        $this->assertFalse($root->subdirectory('foo\bar\baz.txt')->exists());
        $this->assertFalse($root->subdirectory('foo\file.lnk')->exists());
        $this->assertFalse($root->subdirectory('foo\empty\dir')->exists());
        $this->assertFalse($root->subdirectory('inv.lnk')->exists());
    }

    public function test_create_for_existing_directory_is_ignored(): void
    {
        $root = $this->root();
        $root->directory('foo')->create();
        $root->directory('dir.lnk')->create();
        $root->assertStructure($this->exampleStructure());
    }

    public function test_create_for_writable_path_creates_directory(): void
    {
        $root = $this->root();
        $root->directory('bar')->create();
        $root->directory('foo/empty/dir')->create();
        $root->directory('dir.lnk/baz')->create();
        $root->assertStructure($this->exampleStructure([
            'foo' => ['bar' => ['baz' => []], 'empty' => ['dir' => []]],
            'bar' => []
        ]));
    }

    public function test_create_for_not_writable_path_throws_exception(): void
    {
        $cases = [
            'File path'       => [Exception\UnexpectedNodeType::class, 'bar.txt'],
            'File link'       => [Exception\UnexpectedNodeType::class, 'foo/file.lnk'],
            'File descendant' => [Exception\UnexpectedLeafNode::class, 'bar.txt/dir'],
            'Link descendant' => [Exception\UnexpectedLeafNode::class, 'foo/file.lnk/dir']
        ];

        $root = $this->root();
        foreach ($cases as $case => [$exception, $path]) {
            $directory = $root->directory($path);
            $this->assertExceptionType($exception, fn () => $directory->create(), $case);
        }
    }

    public function test_converting_existing_subdirectory_to_root_directory(): void
    {
        $subdirectory = $this->root(['foo' => ['bar' => []]])->directory('foo/bar');
        $newRoot      = $subdirectory->asRoot();

        $this->assertSame($subdirectory->pathname(), $newRoot->pathname());

        $this->assertSame('foo/bar', $subdirectory->name());
        $this->assertSame('', $newRoot->name());

        $this->assertSame('foo/bar/baz.txt', $subdirectory->file('baz.txt')->name());
        $this->assertSame('baz.txt', $newRoot->file('baz.txt')->name());
    }

    public function test_converting_not_existing_subdirectory_to_root_throws_exception(): void
    {
        $directory = $this->root(['foo' => []])->directory('foo/dir');
        $toRoot    = fn () => $directory->asRoot();
        $this->assertExceptionType(Exception\RootDirectoryNotFound::class, $toRoot);
    }

    public function test_files_returns_all_files_iterator(): void
    {
        $root = $this->root([
            'foo'     => ['bar' => ['baz.txt' => '', 'foo.lnk' => '@foo.txt'], 'fizz' => '', 'buzz' => ''],
            'foo.txt' => '',
            'bar.lnk' => '@foo/bar'
        ]);

        $rootDir  = $root->directory();
        $expected = $this->files(['foo.txt', 'foo/bar/baz.txt', 'foo/fizz', 'foo/buzz'], $rootDir);
        $this->assertFiles($expected, $rootDir->files());

        $subDir   = $rootDir->subdirectory('foo');
        $expected = $this->files(['foo/bar/baz.txt', 'foo/buzz', 'foo/fizz'], $rootDir);
        $this->assertFiles($expected, $subDir->files());

        $newRoot  = $rootDir->subdirectory('foo')->asRoot();
        $expected = $this->files(['bar/baz.txt', 'fizz', 'buzz'], $newRoot);
        $this->assertFiles($expected, $newRoot->files());

        $linkedDir = $rootDir->subdirectory('bar.lnk');
        $expected  = $this->files(['bar.lnk/baz.txt'], $rootDir);
        $this->assertFiles($expected, $linkedDir->files());
    }

    public function test_files_for_not_existing_directory_returns_empty_iterator(): void
    {
        $directory = $this->root([])->directory('foo');
        $this->assertFiles([], $directory->files());
    }

    public function test_files_will_iterate_over_currently_existing_files(): void
    {
        $directory = $this->root(['foo' => ['bar' => ['baz' => ''], 'fizz' => '', 'buzz' => '']])->directory();
        $files     = $directory->files();

        $directory->subdirectory('foo/bar')->remove();
        $this->assertFiles([], $directory->subdirectory('foo/bar')->files());
        $this->assertFiles($this->files(['foo/buzz', 'foo/fizz'], $directory), $files);

        $directory->file('foo/bar/baz')->write('file in foo/bar directory');
        $this->assertFiles($this->files(['foo/bar/baz'], $directory), $directory->subdirectory('foo/bar')->files());
        $this->assertFiles($this->files(['foo/buzz', 'foo/fizz', 'foo/bar/baz'], $directory), $files);
    }

    public function test_remove_method_deletes_existing_structure(): void
    {
        $root = $this->root();
        $root->directory('foo')->remove();
        $root->assertStructure($this->exampleStructure(['foo' => null]));
    }

    public function test_remove_method_for_not_existing_directory_is_ignored(): void
    {
        $root = $this->root();
        $root->directory('bar.txt')->remove();
        $root->directory('foo/empty/bar')->remove();
        $root->assertStructure($this->exampleStructure());
    }
}
