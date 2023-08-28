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
use Shudd3r\Filesystem\File;
use Shudd3r\Filesystem\Generic\FileIterator;
use Shudd3r\Filesystem\FilesystemException;
use Shudd3r\Filesystem\Tests\Fixtures\Override;


abstract class FilesystemTests extends TestCase
{
    protected function tearDown(): void
    {
        Override::reset();
    }

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

    /** @return resource */
    protected function resource(string $contents = '')
    {
        $resource = fopen('php://memory', 'rb+');
        fwrite($resource, $contents);
        rewind($resource);
        return $resource;
    }

    /**
     * @param string         $function
     * @param callable|mixed $returnValue fn() => mixed
     * @param mixed          $argValue    Trigger override for this value
     */
    protected function override(string $function, $returnValue, $argValue = null): void
    {
        Override::set($function, $returnValue, $argValue);
    }

    protected function removeOverride(string $function): void
    {
        Override::remove($function);
    }
}