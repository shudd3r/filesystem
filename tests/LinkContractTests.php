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

use Shudd3r\Filesystem\Exception;


trait LinkContractTests
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
        $root->link('foo/file.lnk')->remove();
        $root->link('dir.lnk')->remove();
        $root->link('inv.lnk')->remove();
        $this->assertSameStructure($root, [
            'foo'     => ['bar' => ['baz.txt' => 'baz contents'], 'empty' => []],
            'bar.txt' => 'bar contents'
        ]);
    }

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

    public function test_target_returns_absolute_target_pathname(): void
    {
        $root = $this->root([
            'foo.txt' => '',
            'foo.lnk' => '@foo.txt',
            'dir'     => ['bar' => []],
            'bar.lnk' => '@dir/bar'
        ]);
        $this->assertSame($this->path('foo.txt'), $root->link('foo.lnk')->target());
        $this->assertSame($this->path('dir/bar'), $root->link('bar.lnk')->target());
    }

    public function test_target_for_stale_link_returns_null_unless_explicitly_requested(): void
    {
        $link = $this->root(['stale.lnk' => '@not/exists'])->link('stale.lnk');
        $this->assertSame(null, $link->target());
        $this->assertSame($this->path('not/exists'), $link->target(true));
    }

    public function test_setTarget_for_not_existing_link_creates_link(): void
    {
        $root = $this->root(['foo' => ['bar' => ['baz.txt' => 'contents']]]);
        $root->link('bar.lnk')->setTarget($root->subdirectory('foo/bar'));
        $root->link('baz.lnk')->setTarget($root->file('foo/bar/baz.txt'));
        $this->assertSameStructure($root, [
            'foo'     => ['bar' => ['baz.txt' => 'contents']],
            'bar.lnk' => '@foo/bar',
            'baz.lnk' => '@foo/bar/baz.txt'
        ]);
    }

    public function test_setTarget_for_existing_link_changes_target(): void
    {
        $root = $this->root([
            'foo'      => ['file.old' => '', 'dir.old' => []],
            'bar'      => ['file.new' => '', 'dir.new' => []],
            'file.lnk' => '@foo/file.old',
            'dir.lnk'  => '@foo/dir.old'
        ]);

        $root->link('file.lnk')->setTarget($root->file('bar/file.new'));
        $root->link('dir.lnk')->setTarget($root->subdirectory('bar/dir.new'));

        $this->assertSameStructure($root, [
            'foo'      => ['file.old' => '', 'dir.old' => []],
            'bar'      => ['file.new' => '', 'dir.new' => []],
            'file.lnk' => '@bar/file.new',
            'dir.lnk'  => '@bar/dir.new'
        ]);
    }

    public function test_setTarget_to_external_filesystem_throws_exception(): void
    {
        $link   = $this->root([])->link('foo.lnk');
        $create = fn () => $link->setTarget(new Doubles\FakeFile('fake/file.txt', 'contents'));
        $this->assertExceptionType(Exception\IOException\UnableToCreate::class, $create);
    }

    public function test_setTarget_to_another_link_throws_exception(): void
    {
        $root   = $this->root(['foo' => ['bar.txt' => ''], 'foo.lnk' => '@foo/bar.txt']);
        $create = fn () => $root->link('bar.lnk')->setTarget($root->link('foo.lnk'));
        $this->assertExceptionType(Exception\IOException\UnableToCreate::class, $create);
    }

    public function test_setTarget_to_not_existing_node_throws_exception(): void
    {
        $root   = $this->root([]);
        $create = fn () => $root->link('bar.lnk')->setTarget($root->file('foo/bar.txt'));
        $this->assertExceptionType(Exception\NodeNotFound::class, $create);
    }

    public function test_changing_target_to_different_type_throws_exception(): void
    {
        $root   = $this->root(['foo' => ['bar.file' => '', 'bar.dir' => []], 'bar.lnk' => '@foo/bar.file']);
        $change = fn () => $root->link('bar.lnk')->setTarget($root->subdirectory('foo/bar.dir'));
        $this->assertExceptionType(Exception\UnexpectedNodeType::class, $change);
    }

    public function test_target_node_type_checking(): void
    {
        $root = $this->root();

        $fileLink = $root->link('foo/file.lnk');
        $this->assertTrue($fileLink->isFile());
        $this->assertFalse($fileLink->isDirectory());

        $dirLink = $root->link('dir.lnk');
        $this->assertFalse($dirLink->isFile());
        $this->assertTrue($dirLink->isDirectory());

        $staleLink = $root->link('inv.lnk');
        $this->assertFalse($staleLink->isFile());
        $this->assertFalse($staleLink->isDirectory());
    }
}
