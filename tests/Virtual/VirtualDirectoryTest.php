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

use Shudd3r\Filesystem\Tests\DirectoryTests;
use Shudd3r\Filesystem\Generic\Pathname;


class VirtualDirectoryTest extends DirectoryTests
{
    use VirtualFilesystemSetup;

    public static function pathnameExamples(): array
    {
        return [
            'Windows' => ['A:\\', '\\', 'A:\\foo\\bar\\baz.txt'],
            'Http'    => ['http://example.com', '/', 'http://example.com/foo/bar/baz.txt'],
            'Linux'   => ['/home/user/projects', '/', '/home/user/projects/foo/bar/baz.txt'],
            'Wacky'   => ['*** ROOT\\test', '/', '*** ROOT\\test/foo/bar/baz.txt']
        ];
    }

    /** @dataProvider pathnameExamples */
    public function test_filesystem_works_with_various_paths(string $root, string $separator, string $filePath): void
    {
        $root = $this->root(['foo' => ['bar' => ['baz.txt' => '...']]], [], Pathname::root($root, $separator));

        $file = $root->directory()->file('foo/bar/baz.txt');
        $this->assertTrue($file->exists());
        $this->assertSame($filePath, $file->pathname());
    }
}
