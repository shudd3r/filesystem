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
use Shudd3r\Filesystem\Exception;


class VirtualFileTest extends VirtualFilesystemTests
{
    use FileContractTests;

    public function test_contentStream_returns_null(): void
    {
        $file = $this->root(['foo.txt' => 'contents'])->file('foo.txt');
        $this->assertNull($file->contentStream());
    }

    public function test_self_reference_write_is_ignored(): void
    {
        $root = $this->root(['dir' => ['file.txt' => 'contents']]);
        $file = $root->file('dir');

        $this->assertNotSame($root->file('dir'), $file);
        try {
            $file->copy($root->file('dir'));
        } catch (Exception\UnexpectedNodeType $ex) {
            $this->fail('Exception should not be thrown for ignored operation');
        }

        $file = $root->file('dir/file.txt');
        $file->moveTo($root->subdirectory('dir'));
        $this->assertTrue($file->exists());
    }
}
