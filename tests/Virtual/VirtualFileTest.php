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

use Shudd3r\Filesystem\Tests\FileTests;


class VirtualFileTest extends FileTests
{
    use VirtualFilesystemSetup;

    public function test_contentStream_for_streamable_file_returns_streamable_contents(): void
    {
        $file = $this->root(['foo.txt' => 'foo contents...'])->file('foo.txt');
        $this->assertNull($file->contentStream(), 'VirtualFile is not streamable - contentStream should return null');
    }
}
