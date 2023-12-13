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

use PHPUnit\Framework\TestCase;
use Shudd3r\Filesystem\Virtual\Root;
use Shudd3r\Filesystem\Virtual\Root\TreeNode\Directory;
use Shudd3r\Filesystem\Virtual\Root\TreeNode\File;
use Shudd3r\Filesystem\Virtual\Root\TreeNode\Link;
use Shudd3r\Filesystem\Generic\Pathname;
use LogicException;


class RootTest extends TestCase
{
    private static Root $root;

    public static function setUpBeforeClass(): void
    {
        self::$root = new Root(Pathname::root('vfs://'), new Directory([
            'foo' => new Directory([
                'bar' => new Directory([
                    'baz.txt'  => new File('baz contents'),
                    'self.lnk' => new Link('foo/self.lnk')
                ]),
                'file.lnk' => new Link('bar.txt'),
                'empty'    => new Directory(),
                'self.lnk' => new Link('self.lnk'),
                'root'     => new Link('')
            ]),
            'bar.txt'  => new File('this is bar file'),
            'dir.lnk'  => new Link('foo/bar'),
            'inv.lnk'  => new Link('foo/baz'),
            'red.lnk'  => new Link('dir.lnk'),
            'self.lnk' => new Link('foo/bar/self.lnk')
        ]));
    }

    public function test_node_for_not_matching_root_path_throws_exception(): void
    {
        $this->expectException(LogicException::class);
        self::$root->node('virtual://root/path');
    }

    public function test_node_for_not_existing_path_returns_missing_node(): void
    {
        $missingNodes = [
            'vfs://baz'               => ['vfs://', null],
            'vfs://foo/empty/bar/baz' => ['vfs://foo/empty', null],
            'vfs://dir.lnk/foo/bar'   => ['vfs://dir.lnk', 'vfs://foo/bar/foo/bar']
        ];

        foreach ($missingNodes as $path => [$foundPath, $realPath]) {
            $node = self::$root->node($path);
            $this->assertFalse($node->exists());
            $this->assertTrue($node->isValid());
            $this->assertFalse($node->isDir() || $node->isFile() || $node->isLink());
            $this->assertSame($foundPath, $node->foundPath());
            $this->assertSame($realPath ?? $path, $node->realPath());
        }
    }

    public function test_node_for_invalid_path_returns_invalid_node(): void
    {
        $invalidNodes = [
            'vfs://bar.txt/baz'      => 'vfs://bar.txt',
            'vfs://bar.txt/bar/baz'  => 'vfs://bar.txt',
            'vfs://foo/file.lnk/baz' => 'vfs://foo/file.lnk',
            'vfs://inv.lnk/foo'      => 'vfs://inv.lnk'
        ];

        foreach ($invalidNodes as $path => $foundPath) {
            $node = self::$root->node($path);
            $this->assertFalse($node->exists());
            $this->assertFalse($node->isValid());
            $this->assertFalse($node->isDir() || $node->isFile() || $node->isLink());
            $this->assertSame($foundPath, $node->foundPath());
            $this->assertNull($node->realPath());
        }
    }

    public function test_node_for_existing_path_returns_valid_node(): void
    {
        $existingNodes = [
            'vfs://foo/bar/baz.txt' => [false, true, false, null],
            'vfs://foo/bar'         => [true, false, false, null],
            'vfs://foo/file.lnk'    => [false, true, true, 'vfs://bar.txt'],
            'vfs://dir.lnk'         => [true, false, true, 'vfs://foo/bar'],
            'vfs://inv.lnk'         => [false, false, true, null],
            'vfs://red.lnk'         => [true, false, true, 'vfs://foo/bar'],
            'vfs://red.lnk/baz.txt' => [false, true, false, 'vfs://foo/bar/baz.txt'],
            'vfs://foo/root'        => [true, false, true, 'vfs://']
        ];

        foreach ($existingNodes as $path => [$isDir, $isFile, $isLink, $realPath]) {
            $node = self::$root->node($path);
            $this->assertSame($isDir || $isFile, $node->exists());
            $this->assertTrue($node->isValid());
            $this->assertSame($isDir, $node->isDir());
            $this->assertSame($isFile, $node->isFile());
            $this->assertSame($isLink, $node->isLink());
            $this->assertSame($path, $node->foundPath());
            $this->assertSame($realPath ?? $path, $node->realPath(), $path);
        }
    }

    public function test_resolving_multiple_links(): void
    {
        $node = self::$root->node('vfs://foo/root/red.lnk/baz.txt');
        $this->assertTrue($node->isFile());
        $this->assertSame('vfs://foo/bar/baz.txt', $node->realPath());

        $node = self::$root->node('vfs://foo/root/red.lnk/fizz/buzz.txt');
        $this->assertFalse($node->exists());
        $this->assertTrue($node->isValid());
        $this->assertSame('vfs://foo/bar/fizz/buzz.txt', $node->realPath());

        $node = self::$root->node('vfs://foo/root/inv.lnk');
        $this->assertFalse($node->exists());
        $this->assertTrue($node->isValid());
        $this->assertSame('vfs://inv.lnk', $node->realPath());

        $node = self::$root->node('vfs://foo/root/inv.lnk/bar');
        $this->assertFalse($node->isValid());
        $this->assertSame('vfs://foo/root/inv.lnk', $node->foundPath());
        $this->assertNull($node->realPath());
    }

    public function test_circular_reference_protection(): void
    {
        $node = self::$root->node('vfs://foo/self.lnk');
        $this->assertTrue($node->isValid());
        $this->assertFalse($node->exists());
        $this->assertTrue($node->isLink());
        $this->assertSame('vfs://foo/self.lnk', $node->foundPath());
        $this->assertSame('vfs://foo/self.lnk', $node->realPath());

        $node = self::$root->node('vfs://foo/self.lnk/bar');
        $this->assertFalse($node->isValid());
        $this->assertFalse($node->exists());
        $this->assertSame('vfs://foo/self.lnk', $node->foundPath());
        $this->assertNull($node->realPath());
    }
}
