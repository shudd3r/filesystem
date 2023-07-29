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

use PHPUnit\Framework\TestCase;
use Shudd3r\Filesystem\Local\LocalLink;
use Shudd3r\Filesystem\Local\Pathname;
use Shudd3r\Filesystem\Tests\Fixtures;


class LocalLinkTest extends TestCase
{
    use Fixtures\TempFilesHandling;

    public function test_exists_method(): void
    {
        $link = $this->link('foo/bar');
        $this->assertFalse($link->exists());
        self::$temp->symlink('', 'foo/bar');
        $this->assertTrue($link->exists());
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

    private function link(string $name): LocalLink
    {
        return new LocalLink(Pathname::root(self::$temp->directory())->forChildNode($name));
    }
}
