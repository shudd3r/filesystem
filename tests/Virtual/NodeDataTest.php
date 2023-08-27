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
use Shudd3r\Filesystem\Virtual\NodeData;
use Shudd3r\Filesystem\Exception\IOException;


class NodeDataTest extends TestCase
{
    private const EXAMPLE_STRUCTURE = [
        'foo' => [
            'bar'      => ['baz.txt' => 'baz contents'],
            'file.lnk' => ['/link' => 'bar.txt'],
            'empty'    => []
        ],
        'bar.txt' => 'this is bar file',
        'dir.lnk' => ['/link' => 'foo/bar'],
        'inv.lnk' => ['/link' => 'foo/baz']
    ];

    private static NodeData $tree;

    protected function setUp(): void
    {
        self::$tree = NodeData::root(self::EXAMPLE_STRUCTURE);
    }

    public static function nodeProperties(): array
    {
        return [
            'not exists'  => ['foo/not/exists',  [false, false, false, false, true, '', null, 'not/exists']],
            'inv path'    => ['bar.txt/path',    [false, false, false, false, false, '', null, 'path']],
            'inv lnk'     => ['inv.lnk',         [false, false, false, true, true, '', 'foo/baz', 'baz']],
            'inv lnk ex'  => ['inv.lnk/foo/bar', [false, false, false, false, false, '', 'foo/baz', 'foo/bar']],
            'root dir'    => ['',                [true, true, false, false, true, '', null, '']],
            'dir'         => ['foo',             [true, true, false, false, true, '', null, '']],
            'dir lnk'     => ['dir.lnk',         [true, true, false, true, true, '', 'foo/bar', '']],
            'file'        => ['foo/bar/baz.txt', [true, false, true, false, true, 'baz contents', null, '']],
            'file lnk ex' => ['dir.lnk/baz.txt', [true, false, true, false, true, 'baz contents', null, '']],
            'file lnk'    => ['foo/file.lnk',    [true, false, true, true, true, 'this is bar file', 'bar.txt', '']]
        ];
    }

    /** @dataProvider nodeProperties */
    public function test_node_properties(string $nodePath, array $properties): void
    {
        $keys       = ['exists', 'isDir', 'isFile', 'isLink', 'isValid', 'contents', 'target', 'missing'];
        $properties = array_combine($keys, $properties);

        $node = self::$tree->nodeData($nodePath);
        $this->assertSame($properties['exists'], $node->exists());
        $this->assertSame($properties['isDir'], $node->isDir());
        $this->assertSame($properties['isFile'], $node->isFile());
        $this->assertSame($properties['isLink'], $node->isLink());
        $this->assertSame($properties['isValid'], $node->isValid());
        $this->assertSame($properties['contents'], $node->contents());
        $this->assertSame($properties['target'], $node->target());
        $this->assertSame($properties['missing'], $node->missingPath());
    }

    public function test_root_node(): void
    {
        $node = self::$tree;
        $this->assertSame(['bar.txt', 'foo/bar/baz.txt'], iterator_to_array($node->filenames(), false));
        $this->assertTrue($node->nodeData('subdirectory/path')->isValid());

        $node->putContents('contents');
        $node->setTarget('target/path');
        $this->assertStructure(self::EXAMPLE_STRUCTURE);

        $this->expectException(IOException\UnableToRemove::class);
        $node->remove();
    }

    public function test_not_existing_node(): void
    {
        $node = self::$tree->nodeData('foo/not/exists');
        $this->assertSame([], iterator_to_array($node->filenames(), false));
        $this->assertTrue($node->nodeData('subdirectory/path')->isValid());

        $node->remove();
        $this->assertStructure($expectedTree = self::EXAMPLE_STRUCTURE);

        $node->putContents('new file contents');
        $expectedTree['foo']['not']['exists'] = 'new file contents';
        $this->assertStructure($expectedTree);

        $node->remove();
        unset($expectedTree['foo']['not']['exists']);
        $this->assertStructure($expectedTree);

        $node->setTarget('target/path');
        $expectedTree['foo']['not']['exists']['/link'] = 'target/path';
        $this->assertStructure($expectedTree);
    }

    public function test_directory_node(): void
    {
        $node = self::$tree->nodeData('foo');
        $this->assertSame(['bar/baz.txt'], iterator_to_array($node->filenames(), false));
        $this->assertTrue($node->nodeData('subdirectory/path')->isValid());

        $node->putContents('contents');
        $node->setTarget('target/path');
        $this->assertStructure($expectedTree = self::EXAMPLE_STRUCTURE);

        $node->remove();
        unset($expectedTree['foo']);
        $this->assertStructure($expectedTree);
    }

    public function test_file_node(): void
    {
        $node = self::$tree->nodeData('foo/bar/baz.txt');
        $this->assertSame([], iterator_to_array($node->filenames(), false));
        $this->assertFalse($node->nodeData('subdirectory/path')->isValid());

        $node->putContents('new contents');
        $expectedTree = self::EXAMPLE_STRUCTURE;
        $expectedTree['foo']['bar']['baz.txt'] = 'new contents';
        $this->assertStructure($expectedTree);

        $node->setTarget('target/path');
        $this->assertStructure($expectedTree);

        $node->remove();
        unset($expectedTree['foo']['bar']['baz.txt']);
        $this->assertStructure($expectedTree);
    }

    public function test_linked_file(): void
    {
        $node = self::$tree->nodeData('foo/file.lnk');
        $node->putContents('new contents');
        $expectedTree = self::EXAMPLE_STRUCTURE;
        $expectedTree['bar.txt'] = 'new contents';
        $this->assertStructure($expectedTree);

        $node->setTarget('foo');
        $expectedTree['foo']['file.lnk']['/link'] = 'foo';
        $this->assertStructure($expectedTree);

        $node->remove();
        unset($expectedTree['foo']['file.lnk']);
        $this->assertStructure($expectedTree);
    }

    public function test_linked_directory(): void
    {
        $node = self::$tree->nodeData('dir.lnk');
        $this->assertSame(['baz.txt'], iterator_to_array($node->filenames(), false));
        $this->assertTrue($node->nodeData('subdirectory')->isValid());

        $node->setTarget('foo');
        $expectedTree = self::EXAMPLE_STRUCTURE;
        $expectedTree['dir.lnk']['/link'] = 'foo';
        $this->assertStructure($expectedTree);

        $node->remove();
        unset($expectedTree['dir.lnk']);
        $this->assertStructure($expectedTree);
    }

    public function test_file_in_linked_directory(): void
    {
        $node = self::$tree->nodeData('dir.lnk/baz.txt');
        $node->putContents('new contents');
        $expectedTree = self::EXAMPLE_STRUCTURE;
        $expectedTree['foo']['bar']['baz.txt'] = 'new contents';
        $this->assertStructure($expectedTree);

        $node->setTarget('target/path');
        $this->assertStructure($expectedTree);

        $node->remove();
        $expectedTree = self::EXAMPLE_STRUCTURE;
        unset($expectedTree['foo']['bar']['baz.txt']);
        $this->assertStructure($expectedTree);
    }

    public function test_invalid_link(): void
    {
        $node = self::$tree->nodeData('inv.lnk');
        $node->remove();
        $expectedTree = self::EXAMPLE_STRUCTURE;
        unset($expectedTree['inv.lnk']);
        $this->assertStructure($expectedTree);

        $node->setTarget('foo/baz');
        $node = self::$tree->nodeData('inv.lnk');
        $node->putContents('new contents');
        $expectedTree = self::EXAMPLE_STRUCTURE;
        $expectedTree['foo']['baz'] = 'new contents';
        $this->assertStructure($expectedTree);

        $node->setTarget('target/path');
        $expectedTree['inv.lnk']['/link'] = 'target/path';
        $this->assertStructure($expectedTree);
    }

    private function assertStructure(array $expectedTree): void
    {
        $this->assertEquals(NodeData::root($expectedTree), self::$tree);
    }
}
