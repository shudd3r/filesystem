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

use PHPUnit\Framework\TestCase;
use Shudd3r\Filesystem\Directory;
use Shudd3r\Filesystem\File;
use Shudd3r\Filesystem\Generic\FileIterator;
use Shudd3r\Filesystem\Generic\ContentStream;
use Shudd3r\Filesystem\FilesystemException;


abstract class FilesystemTests extends TestCase
{
    private const EXAMPLE_STRUCTURE = [
        'foo' => [
            'bar'      => ['baz.txt' => 'baz contents'],
            'file.lnk' => '@bar.txt',
            'empty'    => []
        ],
        'bar.txt' => 'bar contents',
        'dir.lnk' => '@foo/bar',
        'inv.lnk' => '@not/exists'
    ];

    abstract protected function root(array $structure = null): Directory;

    abstract protected function path(string $name = ''): string;

    abstract protected function assertSameStructure(Directory $root, array $structure = null): void;

    protected function assertExceptionType(string $expected, callable $procedure, string $case = ''): void
    {
        $title = $case ? 'Case "' . $case . '": ' : '';
        try {
            $procedure();
        } catch (FilesystemException $ex) {
            $message = $title . 'Unexpected Exception type - expected `%s` caught `%s`';
            $this->assertInstanceOf($expected, $ex, sprintf($message, $expected, get_class($ex)));
            return;
        }

        $this->fail(sprintf($title . 'No Exception thrown - expected `%s`', $expected));
    }

    protected function assertFiles(array $files, FileIterator $fileIterator): void
    {
        /** @var File $file */
        foreach ($fileIterator as $file) {
            $name = $file->name();
            $this->assertTrue($file->exists(), sprintf('File `%s` should exist', $name));
            $this->assertArrayHasKey($name, $files, sprintf('Unexpected file `%s` found', $name));
            $this->assertSame($files[$name], $file->pathname());
            unset($files[$name]);
        }
        $this->assertSame([], $files, 'Some expected files were not found');
    }

    protected function files(array $filenames, Directory $root): array
    {
        $files = [];
        foreach ($filenames as $filename) {
            $files[$filename] = $root->file($filename)->pathname();
        }
        return $files;
    }

    protected function stream(string $contents = ''): ContentStream
    {
        $resource = fopen('php://memory', 'rb+');
        fwrite($resource, $contents);
        rewind($resource);
        return new ContentStream($resource);
    }

    protected function exampleStructure(array $merge = []): array
    {
        return $this->mergeStructure(self::EXAMPLE_STRUCTURE, $merge);
    }

    private function mergeStructure($tree, $changes): array
    {
        foreach ($changes as $name => $value) {
            $merge = is_array($value) && isset($tree[$name]);
            $tree[$name] = $merge ? $this->mergeStructure($tree[$name], $value) : $value;
            if ($value === null) { unset($tree[$name]); }
        }

        return $tree;
    }
}
