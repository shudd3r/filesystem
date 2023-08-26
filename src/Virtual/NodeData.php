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

use Shudd3r\Filesystem\Exception\IOException;
use Generator;


class NodeData
{
    public const ROOT = 'virtual://';

    private array     $directory;
    private string    $node;
    private array     $segments;
    private ?NodeData $link;
    private string    $type;

    private function __construct(array &$directory, string $node, array $segments = [], ?NodeData $link = null)
    {
        $this->directory = &$directory;
        $this->node      = $node;
        $this->segments  = $segments;
        $this->link      = $link;
        $this->type      = $this->type();
    }

    public static function root(array $nodes = []): self
    {
        $tree = [self::ROOT => $nodes];
        return new self($tree, self::ROOT);
    }

    public function nodeData(string $path): self
    {
        $segments = $this->pathSegments($path);
        if (!$this->exists() || !$this->isDir()) {
            return new self($this->directory, $this->node, array_merge($this->segments, $segments), $this->link);
        }
        return $segments ? $this->descendantNodeData($segments) : $this;
    }

    public function exists(): bool
    {
        return !$this->segments && isset($this->directory[$this->node]);
    }

    public function isDir(): bool
    {
        return $this->exists() && $this->type === VirtualDirectory::class;
    }

    public function isFile(): bool
    {
        return $this->exists() && $this->type === VirtualFile::class;
    }

    public function isLink(): bool
    {
        return $this->link || $this->exists() && $this->type === VirtualLink::class;
    }

    public function isValid(): bool
    {
        return $this->exists() || $this->type === VirtualDirectory::class;
    }

    public function remove(): void
    {
        if ($this->link) {
            $this->directory = &$this->link->directory;
            $this->node      = $this->link->node;
            $this->segments  = [];
            $this->link      = null;
        }

        if (!$this->exists()) { return; }

        if ($this->node === self::ROOT) {
            throw new IOException\UnableToRemove('Root directory cannot be removed');
        }

        unset($this->directory[$this->node]);
        $this->type = VirtualDirectory::class;
    }

    public function filenames(): Generator
    {
        return $this->generateFilenames($this->isDir() ? $this->directory[$this->node] : []);
    }

    public function contents(): string
    {
        return $this->isFile() ? $this->directory[$this->node] : '';
    }

    public function putContents(string $contents): void
    {
        if (!$this->isValid() || $this->isDir()) { return; }
        if (!$this->exists()) { $this->createPath(); }
        $this->directory[$this->node] = $contents;
        $this->type = VirtualFile::class;
    }

    public function target(): ?string
    {
        if ($this->link) { return $this->link->target(); }
        return $this->type === VirtualLink::class ? $this->directory[$this->node]['/link'] : null;
    }

    public function setTarget(string $path): void
    {
        if ($this->link) { $this->link->setTarget($path); }
        if (!$this->isValid()) { return; }
        if ($this->exists() && $this->type !== VirtualLink::class) { return; }
        if (!$this->exists()) { $this->createPath(); }
        $this->directory[$this->node] = ['/link' => $path];
        $this->type = VirtualLink::class;
    }

    public function type(): ?string
    {
        $value = $this->directory[$this->node] ?? $this->directory;
        if (!is_array($value)) { return VirtualFile::class; }
        return isset($value['/link']) ? VirtualLink::class : VirtualDirectory::class;
    }

    private function generateFilenames(array $directory, string $path = ''): Generator
    {
        ksort($directory, SORT_STRING);
        foreach ($directory as $name => $value) {
            if ($name === '/link') { continue; }
            $pathname = $path ? $path . '/' . $name : $name;
            is_array($value) ? yield from $this->generateFilenames($value, $pathname) : yield $pathname;
        }
    }

    private function descendantNodeData(array $segments, NodeData $linked = null): self
    {
        $parent   = &$this->directory[$this->node];
        $basename = array_shift($segments);

        while ($segments) {
            $subdirectory = isset($parent[$basename]) && is_array($parent[$basename]);
            if (!$subdirectory) { break; }
            if ($link = $parent[$basename]['/link'] ?? null) {
                $node = $this->descendantNodeData($this->pathSegments($link), $linked);
                if (!$node->isDir()) { break; }
                return $node->descendantNodeData($segments, $linked);
            }

            $parent   = &$parent[$basename];
            $basename = array_shift($segments);
        }

        $node = new self($parent, $basename, $segments, $linked);
        $link = $segments ? null : $parent[$basename]['/link'] ?? null;
        return $link ? $this->descendantNodeData($this->pathSegments($link), $node) : $node;
    }

    private function pathSegments(string $path): array
    {
        return $path ? explode('/', $path) : [];
    }

    private function createPath(): void
    {
        if (!$this->segments) { return; }
        array_unshift($this->segments, $this->node);
        $this->node = array_pop($this->segments);
        foreach ($this->segments as $dirname) {
            $this->directory[$dirname] = [];
            $this->directory = &$this->directory[$dirname];
        }
        $this->segments = [];
    }
}
