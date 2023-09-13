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

use Shudd3r\Filesystem\Tests\FilesystemTests;
use Shudd3r\Filesystem\Virtual\VirtualDirectory;
use Shudd3r\Filesystem\Virtual\VirtualFile;
use Shudd3r\Filesystem\Virtual\VirtualLink;


abstract class VirtualFilesystemTests extends FilesystemTests
{
    protected const EXAMPLE_STRUCTURE = [
        'foo' => [
            'bar'      => ['baz.txt' => 'baz contents'],
            'file.lnk' => ['/link' => 'vfs://bar.txt'],
            'empty'    => []
        ],
        'bar.txt' => 'bar contents',
        'dir.lnk' => ['/link' => 'vfs://foo/bar'],
        'inv.lnk' => ['/link' => 'vfs://foo/baz']
    ];

    protected VirtualDirectory $root;

    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__) . '/Fixtures/native-override/virtual.php';
    }

    protected function setUp(): void
    {
        $this->root = VirtualDirectory::root('vfs://', self::EXAMPLE_STRUCTURE);
    }

    protected function directory(string $name = '', array $nodes = null): VirtualDirectory
    {
        $root = $nodes ? VirtualDirectory::root('vfs://', $nodes) : $this->root;
        return $name ? $this->root->subdirectory($name) : $root;
    }

    protected function file(string $name): VirtualFile
    {
        return $this->root->file($name);
    }

    protected function link(string $name): VirtualLink
    {
        return $this->root->link($name);
    }
}
