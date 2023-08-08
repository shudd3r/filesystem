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
        unset($data['parent'][$data['basename']]);
    }

    public function nodeData(VirtualNode $node): ?array
    {
        $data   = $this->pathData($node->pathname(), $node instanceof VirtualLink);
        $exists = $data['type'] && $node instanceof $data['type'];
        return $exists ? $data : null;
    }

    /**
     * @return array{
     *     type: ?string,
     *     basename: string,
     *     path: string,
     *     parent: array,
     *     segments: array,
     *     valid: bool
     * }
     */
    public function pathData(string $pathname, bool $forLink = false): array
    {
        $segments = $this->pathSegments($pathname);
        if (!$segments) {
            return [
                'type'     => VirtualDirectory::class,
                'basename' => '',
                'path'     => $this->root . '/',
                'parent'   => &$this->nodes,
                'segments' => [],
                'valid'    => true
            ];
        }

        $parent   = &$this->nodes;
        $basename = array_shift($segments);
        $path     = $this->root . '/' . $basename;

        while ($segments) {
            $isLink = ($parent[$basename]['link'] ?? false) === true;
            if ($isLink) {
                $data     = $this->pathData($parent[$basename]['target'], $forLink);
                $parent   = &$data['parent'];
                $basename = $data['basename'];
                continue;
            }

            if (!isset($parent[$basename])) { break; }

            $parent   = &$parent[$basename];
            $basename = array_shift($segments);
            $path .= '/' . $basename;
        }

        $type = isset($parent[$basename]) && !$segments ? $this->nodeType($parent[$basename]) : null;
        if (!$forLink && $type === VirtualLink::class) {
            $data     = $this->pathData($parent[$basename]['target']);
            $parent   = &$data['parent'];
            $type     = $data['type'];
            $basename = $data['basename'];
        }

        return [
            'type'     => $type,
            'basename' => $basename,
            'path'     => $path,
            'parent'   => &$parent,
            'segments' => $segments,
            'valid'    => $this->nodeType($parent) === VirtualDirectory::class
        ];
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
