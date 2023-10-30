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

use Shudd3r\Filesystem\Exception;
use Shudd3r\Filesystem\Tests\Doubles;


class LocalLinkTest extends LocalFilesystemTests
{
    public function test_exists_method(): void
    {
        $root = $this->root(['foo' => ['baz.lnk' => '@']]);
        $this->assertFalse($root->link('foo/bar.lnk')->exists());
        $this->assertTrue($root->link('foo/baz.lnk')->exists());
    }

    public function test_remove_method_deletes_link(): void
    {
        $root = $this->root([
            'foo.lnk' => '@foo.txt', 'foo.txt' => '',
            'bar.lnk' => '@foo/bar', 'foo' => ['bar' => []],
            'inv.lnk' => '@not/exists.txt'
        ]);

        $file     = $root->file('foo.txt');
        $fileLink = $root->link('foo.lnk');
        $this->assertTrue($fileLink->exists());
        $this->assertFileExists($fileLink->pathname());
        $fileLink->remove();
        $this->assertFalse($fileLink->exists());
        $this->assertFileDoesNotExist($fileLink->pathname());
        $this->assertFileExists($file->pathname());

        $dir     = $root->subdirectory('foo/bar');
        $dirLink = $root->link('bar.lnk');
        $this->assertTrue($dirLink->exists());
        $this->assertDirectoryExists($dirLink->pathname());
        $dirLink->remove();
        $this->assertFalse($dirLink->exists());
        $this->assertDirectoryDoesNotExist($dirLink->pathname());
        $this->assertDirectoryExists($dir->pathname());

        $staleLink = $root->link('inv.lnk');
        $this->assertTrue($staleLink->exists());
        $this->assertTrue(is_link($staleLink->pathname()));
        $staleLink->remove();
        $this->assertFalse(is_link($staleLink->pathname()));
        $this->assertFalse($staleLink->exists());
    }

    public function test_runtime_remove_failure(): void
    {
        $fileLink = $this->root(['foo.lnk' => '@foo/bar'])->link('foo.lnk');
        $this->assertIOException(Exception\IOException\UnableToRemove::class, fn () => $fileLink->remove(), 'unlink');
    }

    public function test_target_returns_absolute_target_pathname(): void
    {
        $root = $this->root([
            'foo.txt' => '', 'foo' => ['bar' => []],
            'foo.lnk' => '@foo.txt', 'bar.lnk' => '@foo/bar'
        ]);
        $this->assertSame($this->path('foo.txt'), $root->link('foo.lnk')->target());
        $this->assertSame($this->path('foo/bar'), $root->link('bar.lnk')->target());
    }

    public function test_target_for_stale_link_returns_null_unless_explicitly_requested(): void
    {
        $link = $this->root(['stale.lnk' => '@not/exists'])->link('stale.lnk');
        $this->assertSame(null, $link->target());
        $this->assertSame($this->path('not/exists'), $link->target(true));
    }

    public function test_target_node_type_checking(): void
    {
        $root = $this->root([
            'foo.lnk' => '@foo.txt', 'foo.txt' => '',
            'bar.lnk' => '@foo/bar', 'foo' => ['bar' => []],
            'stale.lnk' => '@not/exists.txt'
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
        $path = $link->pathname();
        $this->assertFalse(is_link($path) || is_file($path) || is_dir($path) || file_exists($path));
        $link->setTarget($root->subdirectory('foo/bar'));
        $this->assertDirectoryExists($path);
        $this->assertTrue(is_link($path) && is_dir($path));

        $link = $root->link('baz.lnk');
        $path = $link->pathname();
        $this->assertFalse(is_link($path) || is_file($path) || is_dir($path) || file_exists($path));
        $link->setTarget($root->file('foo/bar/baz.txt'));
        $this->assertFileExists($path);
        $this->assertTrue(is_link($path) && is_file($path));
    }

    public function test_setTarget_for_existing_link_changes_target(): void
    {
        $root = $this->root([
            'foo'      => ['file.old' => '', 'dir.old' => []],
            'bar'      => ['file.new' => '', 'dir.new' => []],
            'file.lnk' => '@foo/file.old',
            'dir.lnk'  => '@foo/dir.old'
        ]);

        $link = $root->link('file.lnk');
        $this->assertSame($this->path('foo/file.old'), $link->target());
        $link->setTarget($root->file('bar/file.new'));
        $this->assertSame($this->path('bar/file.new'), $link->target());

        $link = $root->link('dir.lnk');
        $this->assertSame($this->path('foo/dir.old'), $link->target());
        $link->setTarget($root->subdirectory('bar/dir.new'));
        $this->assertSame($this->path('bar/dir.new'), $link->target());
    }

    public function test_setTarget_to_external_filesystem_throws_exception(): void
    {
        $node = new Doubles\FakeFile('fake/file.txt', 'contents');
        $link = $this->root()->link('foo.lnk');
        $this->assertExceptionType(Exception\IOException\UnableToCreate::class, fn () => $link->setTarget($node));
    }

    public function test_setTarget_to_another_link_throws_exception(): void
    {
        $root = $this->root(['foo' => ['bar.txt' => ''], 'foo.lnk' => '@foo/bar.txt']);
        $node = $root->link('foo.lnk');
        $link = $root->link('bar.lnk');
        $this->assertExceptionType(Exception\IOException\UnableToCreate::class, fn () => $link->setTarget($node));
    }

    public function test_setTarget_to_not_existing_node_throws_exception(): void
    {
        $root = $this->root();
        $node = $root->file('foo/bar.txt');
        $link = $root->link('bar.lnk');
        $this->assertExceptionType(Exception\NodeNotFound::class, fn () => $link->setTarget($node));
    }

    public function test_changing_target_to_different_type_throws_exception(): void
    {
        $root = $this->root(['foo' => ['bar.file' => '', 'bar.dir' => []], 'bar.lnk' => '@foo/bar.file']);
        $node = $root->subdirectory('foo/bar.dir');
        $link = $root->link('bar.lnk');
        $this->assertExceptionType(Exception\UnexpectedNodeType::class, fn () => $link->setTarget($node));
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
