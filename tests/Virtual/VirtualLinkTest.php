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

use Shudd3r\Filesystem\Exception;
use Shudd3r\Filesystem\Tests\Doubles;


class VirtualLinkTest extends VirtualFilesystemTests
{
    public function test_exists_method(): void
    {
        $root = $this->root();
        $this->assertTrue($root->link('foo/file.lnk')->exists());
        $this->assertTrue($root->link('dir.lnk')->exists());
        $this->assertTrue($root->link('inv.lnk')->exists());
        $this->assertFalse($root->link('foo/bar.lnk')->exists());
        $this->assertFalse($root->link('foo/bar')->exists());
        $this->assertFalse($root->link('foo/empty/dir.lnk')->exists());
        $this->assertFalse($root->link('dir.lnk/baz.txt')->exists());
    }

    public function test_exists_for_linked_nodes(): void
    {
        $root = $this->root();
        $this->assertTrue($root->file('foo/file.lnk')->exists());
        $this->assertFalse($root->subdirectory('foo/file.lnk')->exists());
        $this->assertTrue($root->subdirectory('dir.lnk')->exists());
        $this->assertTrue($root->file('dir.lnk/baz.txt')->exists());
        $this->assertFalse($root->file('inv.lnk')->exists());
        $this->assertFalse($root->subdirectory('inv.lnk')->exists());
    }

    public function test_remove_method_deletes_link(): void
    {
        $root = $this->root();
        $link = $root->link('foo/file.lnk');
        $file = $root->file('bar.txt');
        $link->remove();
        $this->assertFalse($link->exists());
        $this->assertTrue($file->exists());

        $link = $root->link('dir.lnk');
        $dir  = $root->subdirectory('foo/bar');
        $link->remove();
        $this->assertFalse($link->exists());
        $this->assertTrue($dir->exists());
    }

    public function test_remove_method_for_linked_node_deletes_link(): void
    {
        $root   = $this->root();
        $linked = $root->file('foo/file.lnk');
        $real   = $root->file('bar.txt');
        $linked->remove();
        $this->assertFalse($linked->exists());
        $this->assertTrue($real->exists());

        $linked = $root->subdirectory('dir.lnk');
        $real   = $root->subdirectory('foo/bar');
        $linked->remove();
        $this->assertFalse($linked->exists());
        $this->assertTrue($real->exists());
    }

    public function test_target_returns_absolute_target_pathname(): void
    {
        $root = $this->root(['foo.txt' => '', 'foo.lnk' => 'foo.txt', 'foo' => ['bar' => []], 'bar.lnk' => 'foo/bar']);
        $this->assertSame('vfs://foo.txt', $root->link('foo.lnk')->target());
        $this->assertSame('vfs://foo/bar', $root->link('bar.lnk')->target());
    }

    public function test_target_for_stale_link_returns_null_unless_explicitly_requested(): void
    {
        $link = $this->root(['stale.lnk' => 'not/exists'])->link('stale.lnk');
        $this->assertSame(null, $link->target());
        $this->assertSame('vfs://not/exists', $link->target(true));
    }

    public function test_target_node_type_checking(): void
    {
        $root = $this->root([
            'foo.lnk' => 'foo.txt', 'foo.txt' => '',
            'bar.lnk' => 'foo/bar', 'foo' => ['bar' => []],
            'stale.lnk' => 'not/exists.txt'
        ]);

        $fileLink = $root->link('foo.lnk');
        $this->assertTrue($fileLink->isFile());
        $this->assertFalse($fileLink->isDirectory());

        $dirLink = $root->link('bar.lnk');
        $this->assertFalse($dirLink->isFile());
        $this->assertTrue($dirLink->isDirectory());

        $staleLink = $root->link('stale.lnk');
        $this->assertFalse($staleLink->isFile());
        $this->assertFalse($staleLink->isDirectory());
    }

    public function test_setTarget_for_not_existing_link_creates_link(): void
    {
        $root = $this->root(['foo' => ['bar' => ['baz.txt' => 'contents']]]);
        $link = $root->link('bar.lnk');
        $link->setTarget($root->subdirectory('foo/bar'));
        $this->assertTrue($link->exists() && $root->subdirectory('bar.lnk')->exists());

        $link = $root->link('baz.lnk');
        $link->setTarget($root->file('foo/bar/baz.txt'));
        $this->assertTrue($link->exists() && $root->file('baz.lnk')->exists());
    }

    public function test_setTarget_for_existing_link_changes_target(): void
    {
        $root = $this->root([
            'foo'      => ['file.old' => '', 'dir.old' => []],
            'bar'      => ['file.new' => '', 'dir.new' => []],
            'file.lnk' => 'foo/file.old',
            'dir.lnk'  => 'foo/dir.old'
        ]);

        $link = $root->link('file.lnk');
        $this->assertSame('vfs://foo/file.old', $link->target());
        $link->setTarget($root->file('bar/file.new'));
        $this->assertSame('vfs://bar/file.new', $link->target());

        $link = $root->link('dir.lnk');
        $this->assertSame('vfs://foo/dir.old', $link->target());
        $link->setTarget($root->subdirectory('bar/dir.new'));
        $this->assertSame('vfs://bar/dir.new', $link->target());
    }

    public function test_setTarget_to_external_filesystem_throws_exception(): void
    {
        $link   = $this->root()->link('foo.lnk');
        $create = fn () => $link->setTarget(new Doubles\FakeFile('fake/file.txt', 'contents'));
        $this->assertExceptionType(Exception\IOException\UnableToCreate::class, $create);
    }

    public function test_setTarget_to_another_link_throws_exception(): void
    {
        $root   = $this->root(['foo' => ['bar.txt' => ''], 'foo.lnk' => 'foo/bar.txt']);
        $link   = $root->link('bar.lnk');
        $create = fn () => $link->setTarget($root->link('foo.lnk'));
        $this->assertExceptionType(Exception\IOException\UnableToCreate::class, $create);
    }

    public function test_setTarget_to_not_existing_node_throws_exception(): void
    {
        $root   = $this->root();
        $link   = $root->link('bar.lnk');
        $create = fn () => $link->setTarget($root->file('foo/bar.txt'));
        $this->assertExceptionType(Exception\NodeNotFound::class, $create);
    }

    public function test_changing_target_to_different_type_throws_exception(): void
    {
        $root = $this->root(['foo' => ['bar.file' => '', 'bar.dir' => []], 'bar.lnk' => 'foo/bar.file']);
        $node = $root->subdirectory('foo/bar.dir');
        $link = $root->link('bar.lnk');
        $this->assertExceptionType(Exception\UnexpectedNodeType::class, fn () => $link->setTarget($node));
    }
}
