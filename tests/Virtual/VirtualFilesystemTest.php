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
use Shudd3r\Filesystem\Generic\ContentStream;
use Shudd3r\Filesystem\Virtual\NodeData;
use Shudd3r\Filesystem\Virtual\VirtualDirectory;
use Shudd3r\Filesystem\Virtual\VirtualFile;
use Shudd3r\Filesystem\Virtual\VirtualLink;
use Shudd3r\Filesystem\Virtual\VirtualNode;
use Shudd3r\Filesystem\Generic\FileIterator;
use Shudd3r\Filesystem\FilesystemException;
use Shudd3r\Filesystem\Exception;
use Shudd3r\Filesystem\Node;
use Shudd3r\Filesystem\Tests\Doubles;
use Shudd3r\Filesystem\Tests\Fixtures;

require_once dirname(__DIR__) . '/Fixtures/native-override/virtual.php';


class VirtualFilesystemTest extends TestCase
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

    private static NodeData $tree;

    protected function setUp(): void
    {
        self::$tree = NodeData::root(self::EXAMPLE_STRUCTURE);
    }

    protected function tearDown(): void
    {
        Fixtures\Override::reset();
    }

    public function test_exists_method(): void
    {
        $this->assertExists($this->directory());
        $this->assertExists($this->file('bar.txt'));
        $this->assertNotExists($this->directory('bar.txt'));
        $this->assertExists($this->directory('foo/bar'));
        $this->assertExists($this->file('foo/bar/baz.txt'));
        $this->assertNotExists($this->link('foo/bar/baz.txt'));
        $this->assertExists($this->link('foo/file.lnk'));
        $this->assertExists($this->file('foo/file.lnk'));
        $this->assertExists($this->link('dir.lnk'));
        $this->assertExists($this->directory('dir.lnk'));
        $this->assertExists($this->file('dir.lnk/baz.txt'));
        $this->assertNotExists($this->file('dir.lnk/link'));
        $this->assertNotExists($this->directory('foo/bar/baz/directory'));
        $this->assertNotExists($this->directory('foo/lnk/directory'));
        $this->assertExists($this->link('inv.lnk'));
        $this->assertNotExists($this->file('inv.lnk'));
    }

    public function test_remove_not_existing_node_is_ignored(): void
    {
        $tree = NodeData::root(self::EXAMPLE_STRUCTURE);
        $this->file('foo/bar/baz/file.txt')->remove();
        $this->assertEquals(self::$tree, $tree);

        $this->directory('foo/bar/baz.txt')->remove();
        $this->assertEquals(self::$tree, $tree);
    }

    public function test_remove_existing_node_removes_node(): void
    {
        $file = $this->file('foo/bar/baz.txt');
        $file->remove();
        $this->assertNotExists($file);

        $link = $this->link('foo/lnk');
        $link->remove();
        $this->assertNotExists($link);
        $this->assertExists($this->file('bar.txt'));

        $directory    = $this->directory('foo');
        $subdirectory = $directory->subdirectory('bar');
        $this->assertExists($subdirectory);
        $directory->remove();
        $this->assertNotExists($subdirectory);
        $this->assertNotExists($directory);
    }

    public function test_remove_linked_node_removes_link(): void
    {
        $file = $this->file('foo/lnk');
        $file->remove();
        $this->assertNotExists($file);
        $this->assertNotExists($this->link('foo/lnk'));
        $this->assertExists($this->file('bar.txt'));

        $directory = $this->directory('bar.lnk');
        $directory->remove();
        $this->assertNotExists($directory);
        $this->assertNotExists($this->link('bar.lnk'));
        $this->assertExists($this->directory('foo/bar'));

        $file = $this->file('inv.lnk');
        $file->remove();
        $this->assertExists($this->link('inv.lnk'));
    }

    public function test_node_permissions(): void
    {
        $file = $this->file('foo/bar/baz.txt');
        $this->assertTrue($file->isReadable());
        $this->assertTrue($file->isWritable());
        $this->assertTrue($file->isRemovable());
    }

    public function test_directory_methods(): void
    {
        $directory = $this->directory();
        $this->assertSame('virtual://', $directory->pathname());
        $this->assertSame('', $directory->name());

        $directory = $this->directory('foo');
        $this->assertSame('virtual://foo', $directory->pathname());
        $this->assertSame('foo', $directory->name());
        $subdirectory = $directory->subdirectory('bar/baz');
        $this->assertSame('foo/bar/baz', $subdirectory->name());

        $directory = $directory->asRoot();
        $this->assertSame('', $directory->name());
        $subdirectory = $directory->subdirectory('bar/baz');
        $this->assertSame('bar/baz', $subdirectory->name());
        $this->assertSame('virtual://foo/bar/baz', $subdirectory->pathname());
        $this->assertSame($subdirectory, $subdirectory->validated());
        $this->assertExists($directory->file('bar/baz.txt'));
        $this->assertExists($directory->link('file.lnk'));

        $notExistingRoot = fn () => $subdirectory->asRoot();
        $this->assertExceptionType(Exception\RootDirectoryNotFound::class, $notExistingRoot);

        $validateExisting = fn () => $subdirectory->validated(Node::EXISTS);
        $this->assertExceptionType(Exception\NodeNotFound::class, $validateExisting);

        $validatePath = fn () => $directory->subdirectory('bar/baz.txt/dir')->validated();
        $this->assertExceptionType(Exception\UnexpectedLeafNode::class, $validatePath);

        $validatePath = fn () => $directory->subdirectory('bar/baz.txt')->validated();
        $this->assertExceptionType(Exception\UnexpectedNodeType::class, $validatePath);
    }

    public function test_directory_file_iteration(): void
    {
        $directory = $this->directory();
        $expected  = ['bar.txt' => 'virtual://bar.txt', 'foo/bar/baz.txt' => 'virtual://foo/bar/baz.txt'];
        $this->assertFiles($expected, $directory->files());

        $directory = $this->directory('foo');
        $expected  = ['foo/bar/baz.txt' => 'virtual://foo/bar/baz.txt'];
        $this->assertFiles($expected, $directory->files());

        $expected = ['bar/baz.txt' => 'virtual://foo/bar/baz.txt'];
        $this->assertFiles($expected, $directory->asRoot()->files());

        $directory = $this->directory('foo/empty');
        $this->assertFiles([], $directory->files());

        $directory = $this->directory('foo/not-exists');
        $this->assertFiles([], $directory->files());
    }

    public function test_file_contents(): void
    {
        $this->assertSame('baz contents', $this->file('foo/bar/baz.txt')->contents());
        $this->assertSame('bar contents', $this->file('foo/file.lnk')->contents());

        $this->assertNull($this->file('foo/bar/baz.txt')->contentStream());

        $file = $this->file('foo/file.lnk/bar.txt');
        $this->assertExceptionType(Exception\UnexpectedLeafNode::class, fn () => $file->contents(), 'invalid path');

        $file = $this->file('foo');
        $this->assertExceptionType(Exception\UnexpectedNodeType::class, fn () => $file->contents(), 'invalid type');
    }

    public function test_file_write(): void
    {
        $file = $this->file('baz/file.txt');
        $this->assertSame('', $file->contents());

        $file->write('contents');
        $this->assertSame('contents', $file->contents());

        $file->append('-appended');
        $this->assertSame('contents-appended', $file->contents());

        $file->writeStream($this->stream());
        $this->assertSame('stream contents', $file->contents());

        $this->override('stream_get_contents', false);
        $writeStream = fn () => $file->writeStream($this->stream());
        $this->assertExceptionType(Exception\IOException\UnableToReadContents::class, $writeStream);

        $file->copy($this->file('foo/file.lnk'));
        $this->assertSame('bar contents', $file->contents());

        $file->moveTo($this->directory('bar'));
        $this->assertFalse($file->exists());
        $this->assertSame('bar contents', $this->file('bar/file.txt')->contents());
    }

    public function test_link_target(): void
    {
        $link = $this->link('foo/file.lnk');
        $this->assertSame('virtual://bar.txt', $link->target());
        $this->file('bar.txt')->remove();
        $this->assertNull($link->target());
        $this->assertSame('virtual://bar.txt', $link->target(true));

        $link = $this->link('foo/file.lnk/bar.txt');
        $this->assertExceptionType(Exception\UnexpectedLeafNode::class, fn () => $link->target(), 'invalid path');

        $link = $this->link('foo');
        $this->assertExceptionType(Exception\UnexpectedNodeType::class, fn () => $link->target(), 'invalid type');
    }

    public function test_link_setTarget(): void
    {
        $link = $this->link('foo/file.lnk');
        $link->setTarget($this->file('foo/bar/baz.txt'));
        $this->assertSame('virtual://foo/bar/baz.txt', $link->target());

        $directoryTarget = fn () => $link->setTarget($this->directory('foo'));
        $this->assertExceptionType(Exception\UnexpectedNodeType::class, $directoryTarget);

        $filesystemMismatch = fn () => $link->setTarget(new Doubles\FakeLocalNode());
        $this->assertExceptionType(Exception\IOException\UnableToCreate::class, $filesystemMismatch);

        $indirectTarget = fn () => $link->setTarget($this->link('dir.lnk'));
        $this->assertExceptionType(Exception\IOException\UnableToCreate::class, $indirectTarget);
    }

    public function test_invalid_link(): void
    {
        $validate = fn () => $this->directory('inv.lnk')->validated();
        $this->assertExceptionType(Exception\UnexpectedNodeType::class, $validate, 'directory');

        $validate = fn () => $this->directory('inv.lnk/subdirectory')->validated();
        $this->assertExceptionType(Exception\UnexpectedLeafNode::class, $validate, 'subdirectory');

        $validate = fn () => $this->file('inv.lnk')->validated();
        $this->assertExceptionType(Exception\UnexpectedNodeType::class, $validate, 'file');

        $validate = fn () => $this->file('inv.lnk/foo/file.txt')->validated();
        $this->assertExceptionType(Exception\UnexpectedLeafNode::class, $validate, 'subdirectory file');
    }

    private function assertExists(VirtualNode $node): void
    {
        $this->assertTrue($node->exists());
    }

    private function assertNotExists(VirtualNode $node): void
    {
        $this->assertFalse($node->exists());
    }

    private function assertExceptionType(string $expected, callable $procedure, string $case = ''): void
    {
        $title = $case ? 'Case "' . $case . '": ' : '';
        try {
            $procedure();
        } catch (FilesystemException $ex) {
            $message = $title . 'Unexpected Exception type - expected `%s` caught `%s`';
            $this->assertInstanceOf($expected, $ex, sprintf($message, $expected, get_class($ex)));
            return;
        }

        $this->fail(sprintf($title . 'No Exception thrown - expected `%s`', $expected));
    }

    private function assertFiles(array $files, FileIterator $fileIterator): void
    {
        /** @var VirtualFile $file */
        foreach ($fileIterator as $file) {
            $name = $file->name();
            $this->assertTrue($file->exists(), sprintf('File `%s` should exist', $name));
            $this->assertArrayHasKey($name, $files, sprintf('Unexpected file `%s` found', $name));
            $this->assertSame($files[$name], $file->pathname());
            unset($files[$name]);
        }
        $this->assertSame([], $files, 'Some expected files were not found');
    }

    private function directory(string $name = ''): VirtualDirectory
    {
        return new VirtualDirectory(self::$tree, '', $name);
    }

    private function file(string $name): VirtualFile
    {
        return new VirtualFile(self::$tree, '', $name);
    }

    private function link(string $name): VirtualLink
    {
        return new VirtualLink(self::$tree, '', $name);
    }

    private function stream(): ContentStream
    {
        $resource = fopen('php://memory', 'rb+');
        fwrite($resource, 'stream contents');
        rewind($resource);
        return new ContentStream($resource);
    }

    private function override(string $function, $value, $argValue = null): void
    {
        Fixtures\Override::set($function, $value, $argValue);
    }
}
