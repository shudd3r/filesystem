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
        return $this->nodeData($node) !== null;
    }

    public function remove(VirtualNode $node): void
    {
        $data   = $this->pathData($node->pathname(), true);
        $exists = $data['type'] && $node instanceof $data['type'] || $data['type'] === VirtualLink::class;
        if (!$exists) { return; }
        unset($data['parent'][$data['segments'][0]]);
    }

    public function nodeData(VirtualNode $node): ?array
    {
        $data   = $this->pathData($node->pathname(), $node instanceof VirtualLink);
        $exists = $data['type'] && $node instanceof $data['type'];
        return $exists ? $data : null;
    }

    /**
     * @return array{type: ?string, parent: array, segments: array}
     */
    public function pathData(string $pathname, bool $forLink = false): array
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
