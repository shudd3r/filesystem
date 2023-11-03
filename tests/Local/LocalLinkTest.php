<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Tests\Local;

use Shudd3r\Filesystem\Tests\LinkContractTests;
use Shudd3r\Filesystem\Exception;


class LocalLinkTest extends LocalFilesystemTests
{
    use LinkContractTests;

    public function test_runtime_remove_failure(): void
    {
        $fileLink = $this->root(['foo.lnk' => '@foo/bar'])->link('foo.lnk');
        $this->assertIOException(Exception\IOException\UnableToRemove::class, fn () => $fileLink->remove(), 'unlink');
    }

    public function test_runtime_setTarget_failures(): void
    {
        $root = $this->root(['foo' => ['bar.txt' => '', 'baz.txt' => ''], 'baz.lnk' => '@foo/baz.txt']);

        $setTarget = fn () => $root->link('bar.lnk')->setTarget($root->file('foo/bar.txt'));
        $this->assertIOException(Exception\IOException\UnableToCreate::class, $setTarget, 'symlink');

        $setTarget = fn () => $root->link('baz.lnk')->setTarget($root->file('foo/bar.txt'));
        $this->assertIOException(Exception\IOException\UnableToCreate::class, $setTarget, 'rename');
    }
}
