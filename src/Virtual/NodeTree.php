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

use Shudd3r\Filesystem\Exception;


class NodeTree
{
    private string $root = 'virtual:/';
    private array  $nodes;

    public function __construct(array $nodes = [])
    {
        $this->nodes = $nodes;
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

    public function contentsOf(VirtualFile $file): string
    {
        $data = $this->pathData($file->pathname());
        return $data['type'] === VirtualFile::class ? $data['parent'][$data['segments'][0]] : '';
    }

    public function targetOf(VirtualLink $link, bool $showRemoved): ?string
    {
        $data = $this->pathData($link->pathname(), true);
        $path = $data['type'] === VirtualLink::class ? $data['parent'][$data['segments'][0]]['target'] : null;
        return $showRemoved || !$path || $this->pathData($path)['type'] ? $path : null;
    }

    /**
     * @return array{type: ?string, parent: array, segments: array}
     */
    private function pathData(string $pathname, bool $forLink = false): array
    {
        if (!$segments = $this->pathSegments($pathname)) {
            return ['type' => VirtualDirectory::class, 'parent' => &$this->nodes, 'segments' => []];
        }

        $parent   = &$this->nodes;
        $basename = array_shift($segments);

        while ($segments) {
            $subdirectory = isset($parent[$basename]) && is_array($parent[$basename]);
            if (!$subdirectory) { break; }
            $isLink = ($parent[$basename]['link'] ?? false) === true;
            if ($isLink) {
                $data     = $this->pathData($parent[$basename]['target'], $forLink);
                $parent   = &$data['parent'];
                $basename = $data['segments'][0];
                continue;
            }

            $parent   = &$parent[$basename];
            $basename = array_shift($segments);
        }

        $type = isset($parent[$basename]) && !$segments ? $this->nodeType($parent[$basename]) : null;
        return $forLink || $type !== VirtualLink::class
            ? ['type' => $type, 'parent' => &$parent, 'segments' => array_merge([$basename], $segments)]
            : $this->pathData($parent[$basename]['target']);
    }

    private function pathSegments(string $pathname): array
    {
        $path = substr($pathname, strlen($this->root) + 1);
        return $path ? explode('/', $path) : [];
    }

    private function nodeType($value): string
    {
        if (!is_array($value)) { return VirtualFile::class; }

        $isLink = $value['link'] ?? null === true;
        return $isLink ? VirtualLink::class : VirtualDirectory::class;
    }
}
