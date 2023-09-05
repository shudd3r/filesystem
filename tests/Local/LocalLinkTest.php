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

use Shudd3r\Filesystem\Local\LocalLink;
use Shudd3r\Filesystem\Local\LocalDirectory;
use Shudd3r\Filesystem\Exception;
use Shudd3r\Filesystem\Tests\Doubles;


class LocalLinkTest extends LocalFilesystemTests
{
    public function test_exists_method(): void
    {
        $link = $this->link('foo/bar');
        $this->assertFalse($link->exists());
        self::$temp->symlink('', 'foo/bar');
        $this->assertTrue($link->exists());
    }

    public function test_remove_method_deletes_link(): void
    {
        self::$temp->symlink($file = self::$temp->file('foo.txt'), 'file.lnk');
        $fileLink = $this->link('file.lnk');
        $this->assertTrue($fileLink->exists());
        $fileLink->remove();
        $this->assertFalse($fileLink->exists());
        $this->assertFileDoesNotExist($fileLink->pathname());
        $this->assertFileExists($file);

        self::$temp->symlink($directory = self::$temp->directory('foo/bar'), 'dir.lnk');
        $dirLink = $this->link('dir.lnk');
        $this->assertTrue($dirLink->exists());
        $dirLink->remove();
        $this->assertFalse($dirLink->exists());
        $this->assertFileDoesNotExist($dirLink->pathname());
        $this->assertFileExists($directory);

        self::$temp->symlink('', 'stale.lnk');
        $staleLink = $this->link('stale.lnk');
        $this->assertTrue($staleLink->exists());
        $staleLink->remove();
        $this->assertFalse($staleLink->exists());
        $this->assertFileDoesNotExist($staleLink->pathname());
    }

    public function test_runtime_remove_failure(): void
    {
        self::$temp->symlink(self::$temp->file('foo.txt'), 'file.lnk');
        $fileLink = $this->link('file.lnk');
        $this->assertIOException(Exception\IOException\UnableToRemove::class, fn () => $fileLink->remove(), 'unlink');
    }

    public function test_target_returns_absolute_target_pathname(): void
    {
        self::$temp->symlink($target = self::$temp->file('foo.txt'), 'file.lnk');
        $link = $this->link('file.lnk');
        $this->assertSame($target, $link->target());

        self::$temp->symlink($target = self::$temp->directory('foo/bar'), 'dir.lnk');
        $link = $this->link('dir.lnk');
        $this->assertSame($target, $link->target());
    }

    public function test_target_for_stale_link_returns_null_unless_explicitly_requested(): void
    {
        $deleted = self::$temp->file('foo/bar.txt');
        self::$temp->symlink($deleted, 'stale.lnk');
        self::$temp->remove($deleted);
        $link = $this->link('stale.lnk');
        $this->assertSame(null, $link->target());
        $this->assertSame($deleted, $link->target(true));
    }

    public function test_target_node_type_checking(): void
    {
        self::$temp->symlink(self::$temp->file('foo.txt'), 'file.lnk');
        $fileLink = $this->link('file.lnk');
        $this->assertTrue($fileLink->isFile());
        $this->assertFalse($fileLink->isDirectory());

        self::$temp->symlink(self::$temp->directory('foo/bar'), 'dir.lnk');
        $dirLink = $this->link('dir.lnk');
        $this->assertFalse($dirLink->isFile());
        $this->assertTrue($dirLink->isDirectory());

        self::$temp->symlink('', 'stale.lnk');
        $staleLink = $this->link('stale.lnk');
        $this->assertFalse($staleLink->isFile());
        $this->assertFalse($staleLink->isDirectory());
    }

    public function test_setTarget_for_not_existing_link_creates_link(): void
    {
        self::$temp->file('foo/bar/baz.file');
        $node = $this->node('foo/bar');
        $link = $this->link('bar.lnk');
        $this->assertFileDoesNotExist($link->pathname());
        $link->setTarget($node);
        $this->assertFileExists($link->pathname());
        $this->assertTrue(is_link($link->pathname()));

        $link = $this->link('baz.lnk');
        $this->assertFileDoesNotExist($link->pathname());
        $link->setTarget($node);
        $this->assertFileExists($link->pathname());
        $this->assertTrue(is_link($link->pathname()));
    }

    public function test_setTarget_for_existing_link_changes_target(): void
    {
        $old = self::$temp->file('foo/bar/baz.old');
        $new = self::$temp->file('foo/bar/baz.new');
        self::$temp->symlink($old, 'baz.lnk');
        $link = $this->link('baz.lnk');
        $link->setTarget($this->node('foo/bar/baz.new'));
        $this->assertSame($new, $link->target());

        $old = self::$temp->directory('foo/bar.old');
        $new = self::$temp->directory('foo/bar.new');
        self::$temp->symlink($old, 'bar.lnk');
        $link = $this->link('bar.lnk');
        $link->setTarget($this->node('foo/bar.new'));
        $this->assertSame($new, $link->target());
    }

    public function test_setTarget_to_external_filesystem_throws_exception(): void
    {
        $node = new Doubles\FakeFile('fake/file.txt', 'contents');
        $link = $this->link('foo.lnk');
        $this->assertExceptionType(Exception\IOException\UnableToCreate::class, fn () => $link->setTarget($node));
    }

    public function test_setTarget_to_another_link_throws_exception(): void
    {
        self::$temp->symlink(self::$temp->file('foo/bar.txt'), 'foo.lnk');
        $node = $this->link('foo.lnk');
        $link = $this->link('bar.lnk');
        $this->assertExceptionType(Exception\IOException\UnableToCreate::class, fn () => $link->setTarget($node));
    }

    public function test_setTarget_to_not_existing_node_throws_exception(): void
    {
        $node = $this->node('foo/bar', false);
        $link = $this->link('bar.lnk');
        $this->assertExceptionType(Exception\NodeNotFound::class, fn () => $link->setTarget($node));
    }

    public function test_changing_target_to_different_type_throws_exception(): void
    {
        self::$temp->file('foo/bar.file');
        self::$temp->symlink(self::$temp->directory('foo/bar.dir'), 'bar.lnk');
        $node = $this->node('foo/bar.file');
        $link = $this->link('bar.lnk');
        $this->assertExceptionType(Exception\UnexpectedNodeType::class, fn () => $link->setTarget($node));
    }

    public function test_runtime_setTarget_failures(): void
    {
        $file = self::$temp->file('foo/bar.txt');
        $link = $this->link('bar.lnk');

        $setTarget = fn () => $link->setTarget($this->node('foo/bar.txt'));
        $this->assertIOException(Exception\IOException\UnableToCreate::class, $setTarget, 'symlink');

        self::$temp->symlink($file, 'bar.lnk');
        self::$temp->file('foo/baz.txt');

        $setTarget = fn () => $link->setTarget($this->node('foo/baz.txt'));
        $this->assertIOException(Exception\IOException\UnableToCreate::class, $setTarget, 'rename');
    }

    private function link(string $name): LocalLink
    {
        return LocalDirectory::root(self::$temp->directory())->link($name);
    }

    private function node(string $name, bool $exists = true): Doubles\FakeLocalNode
    {
        return new Doubles\FakeLocalNode(self::$temp->directory(), $name, $exists);
    }
}
