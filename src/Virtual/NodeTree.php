<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Virtual;

use Shudd3r\Filesystem\Generic\FileGenerator;
use Shudd3r\Filesystem\Generic\FileIterator;
use Shudd3r\Filesystem\Exception;
use Generator;


class NodeTree
{
    public const ROOT = 'virtual://';

    private array $nodes;

    public function __construct(array $nodes = [])
    {
        $this->nodes = [self::ROOT => $nodes];
    }

    public function exists(VirtualNode $node): bool
    {
        $type = $this->pathData($node->pathname(), $node instanceof VirtualLink)['type'];
        return $type && $node instanceof $type;
    }

    public function remove(VirtualNode $node): void
    {
        $data   = $this->pathData($node->pathname(), true);
        $exists = $data['type'] && $node instanceof $data['type'] || $data['type'] === VirtualLink::class;
        if (!$exists) { return; }
        unset($data['parent'][$data['segments'][0]]);
    }

    public function validate(VirtualNode $node, int $flags): void
    {
        $data   = $this->pathData($node->pathname());
        $exists = $data['type'] && $node instanceof $data['type'];
        if ($exists) { return; }
        if ($flags & VirtualNode::EXISTS) {
            throw new Exception\NodeNotFound();
        }

        $validPath = !$data['type'] && !isset($data['parent'][$data['segments'][0]]);
        if ($validPath) { return; }
        throw $data['type'] ? new Exception\UnexpectedNodeType() : new Exception\UnexpectedLeafNode();
    }

    public function directoryFiles(VirtualDirectory $directory, string $root): FileIterator
    {
        $path = $directory->pathname();
        $data = $this->pathData($path);
        if ($data['type'] !== VirtualDirectory::class) {
            return FileIterator::fromArray([]);
        }

        $node = $data['parent'][$data['segments'][0]];
        return new FileIterator(new FileGenerator(fn () => $this->generateFiles($node, $root, $directory->name())));
    }

    public function contentsOf(VirtualFile $file): string
    {
        $data = $this->pathData($file->pathname());
        return $data['type'] === VirtualFile::class ? $data['parent'][$data['segments'][0]] : '';
    }

    public function targetOf(VirtualLink $link, bool $showRemoved): ?string
    {
        $data = $this->pathData($link->pathname(), true);
        $path = $data['type'] === VirtualLink::class ? $data['parent'][$data['segments'][0]]['/link'] : null;
        return $showRemoved || !$path || $this->pathData($path)['type'] ? $path : null;
    }

    /**
     * @return array{type: ?string, parent: array, segments: array}
     */
    private function pathData(string $pathname, bool $forLink = false): array
    {
        if (!$segments = $this->pathSegments($pathname)) {
            return ['type' => VirtualDirectory::class, 'parent' => &$this->nodes, 'segments' => [self::ROOT]];
        }

        $parent   = &$this->nodes[self::ROOT];
        $basename = array_shift($segments);

        while ($segments) {
            $subdirectory = isset($parent[$basename]) && is_array($parent[$basename]);
            if (!$subdirectory) { break; }
            $isLink = $parent[$basename]['/link'] ?? null;
            if ($isLink) {
                $data = $this->pathData($parent[$basename]['/link'], $forLink);
                if ($data['type'] !== VirtualDirectory::class) { break; }
                $parent   = &$data['parent'];
                $basename = $data['segments'][0];
                continue;
            }

            $parent   = &$parent[$basename];
            $basename = array_shift($segments);
        }

        $type = isset($parent[$basename]) && !$segments ? $this->nodeType($parent[$basename]) : null;
        if (!$forLink && $type === VirtualLink::class) {
            $data = $this->pathData($parent[$basename]['/link']);
            if ($data['type']) { return $data; }
        }

        return ['type' => $type, 'parent' => &$parent, 'segments' => array_merge([$basename], $segments)];
    }

    private function pathSegments(string $pathname): array
    {
        $path = substr($pathname, strlen(self::ROOT));
        return $path ? explode('/', $path) : [];
    }

    private function nodeType($value): string
    {
        if (!is_array($value)) { return VirtualFile::class; }
        return isset($value['/link']) ? VirtualLink::class : VirtualDirectory::class;
    }

    private function generateFiles(array $directory, string $root, string $path = ''): Generator
    {
        foreach ($directory as $name => $value) {
            if ($name === '/link') { continue; }
            $pathname = $path ? $path . '/' . $name : $name;
            is_array($value)
                ? yield from $this->generateFiles($value, $root, $pathname)
                : yield new VirtualFile($this, $root, $pathname);
        }
    }
}
