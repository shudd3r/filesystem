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
use Shudd3r\Filesystem\Virtual\VirtualNode;
use Shudd3r\Filesystem\Virtual\VirtualDirectory;
use Shudd3r\Filesystem\Virtual\VirtualFile;
use Shudd3r\Filesystem\Virtual\VirtualLink;
use Shudd3r\Filesystem\Virtual\NodeData;
use Shudd3r\Filesystem\Tests\Doubles;


abstract class VirtualFilesystemTests extends FilesystemTests
{
    private const EXAMPLE_STRUCTURE = [
        'foo' => [
            'bar'      => ['baz.txt' => 'baz contents'],
            'file.lnk' => ['/link' => 'bar.txt'],
            'empty'    => []
        ],
        'bar.txt' => 'bar contents',
        'dir.lnk' => ['/link' => 'foo/bar'],
        'inv.lnk' => ['/link' => 'foo/baz']
    ];

    protected NodeData $nodes;

    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__) . '/Fixtures/native-override/virtual.php';
    }

    protected function setUp(): void
    {
        $this->nodes = $this->nodes();
    }

    protected function node(string $name = '', string $root = '', bool $exists = true): VirtualNode
    {
        return new Doubles\FakeVirtualNode($this->nodes, $root, $name, $exists);
    }

    protected function directory(string $name = '', string $root = ''): VirtualDirectory
    {
        return new VirtualDirectory($this->nodes, $root, $name);
    }

    protected function file(string $name, string $root = ''): VirtualFile
    {
        return new VirtualFile($this->nodes, $root, $name);
    }

    protected function link(string $name, string $root = ''): VirtualLink
    {
        return new VirtualLink($this->nodes, $root, $name);
    }

    protected function nodes(array $data = null): NodeData
    {
        return NodeData::root($data ?? self::EXAMPLE_STRUCTURE);
    }

    /**
     * @param array<string>               $removePaths
     * @param array<string, string|array> $addPathValues
     */
    protected function example(array $removePaths = [], array $addPathValues = []): array
    {
        $tree = self::EXAMPLE_STRUCTURE;
        foreach ($addPathValues as $path => $value) {
            $segments = explode('/', $path);
            $current  = &$tree;
            while ($segment = array_shift($segments)) {
                $current[$segment] ??= [];
                $current = &$current[$segment];
            }
            $current = $value;
        }

        foreach ($removePaths as $path) {
            $segments = explode('/', $path);
            $node     = array_pop($segments);
            $current  = &$tree;
            while ($segment = array_shift($segments)) {
                if (!isset($current[$segment])) { continue 2; }
                $current = &$current[$segment];
            }
            unset($current[$node]);
        }
        return $tree;
    }
}
