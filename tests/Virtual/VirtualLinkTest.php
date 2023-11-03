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

use Shudd3r\Filesystem\Tests\LinkContractTests;


class VirtualLinkTest extends VirtualFilesystemTests
{
    use LinkContractTests;

    public function test_remove_method_for_linked_node_deletes_link(): void
    {
        $root = $this->root();
        $root->file('foo/file.lnk')->remove();
        $root->subdirectory('dir.lnk')->remove();
        $this->assertSameStructure($root, [
            'foo'     => ['bar' => ['baz.txt' => 'baz contents'], 'empty' => []],
            'bar.txt' => 'bar contents',
            'inv.lnk' => '@not/exists'
        ]);
    }
}
