<?php

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Tests\Generic;

use PHPUnit\Framework\TestCase;
use Shudd3r\Filesystem\Generic\FileIterator;
use Shudd3r\Filesystem\Virtual\BasicVirtualFile as VirtualFile;
use Shudd3r\Filesystem\File;


class FileListTest extends TestCase
{
    private static array $example;

    public static function setUpBeforeClass(): void
    {
        self::$example = self::filesArray(['foo.txt' => 'something', 'foo/bar.ext' => '', 'baz' => null]);
    }

    public function test_instantiation_from_array_and_ArrayIterator_instance_returns_same_array(): void
    {
        $this->assertSame(self::$example, $this->files()->list());
        $this->assertSame(self::$example, FileIterator::fromArray(self::$example)->list());
    }

    public function test_find_returns_null_when_no_file_is_found(): void
    {
        $criteria = fn (File $file) => in_array($file->name(), ['foo.doc', 'baz.txt'], true);
        $this->assertNull($this->files()->find($criteria));
    }

    public function test_find_returns_first_file_that_meets_criteria(): void
    {
        $criteria = fn (File $file) => $file->exists();
        $this->assertSame(self::$example[0], $this->files()->find($criteria));
    }

    public function test_select_returns_new_instance_without_filtered_files(): void
    {
        $filtered = $this->files()->select(fn (File $file) => $file->exists());
        $this->assertSame(array_slice(self::$example, 0, 2), $filtered->list());

        $filtered = $filtered->select(fn (File $file) => str_ends_with($file->name(), '.ext'));
        $this->assertSame(array_slice(self::$example, 1, 1), $filtered->list());
    }

    public function test_map_converts_files_to_array_list_of_transformed_types(): void
    {
        $this->assertSame(['something', '', ''], $this->files()->map(fn (File $file) => $file->contents()));
    }

    public function test_forEach_executes_procedure_on_each_file(): void
    {
        $capture = [];
        $this->files()->forEach(function (File $file) use (&$capture): void { $capture[] = $file->name(); });
        $this->assertSame(['foo.txt', 'foo/bar.ext', 'baz'], $capture);
    }

    private static function filesArray(array $files = null): array
    {
        $fileArray = [];
        foreach ($files ?? self::$example as $name => $contents) {
            $fileArray[] = new VirtualFile($name, $contents);
        }
        return $fileArray;
    }

    private static function files(): FileIterator
    {
        return FileIterator::fromArray(self::$example);
    }
}
