<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Local;

use Shudd3r\Filesystem\Exception\InvalidNodeName;
use Shudd3r\Filesystem\Exception\RootDirectoryNotFound;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;
use Iterator;


final class Pathname
{
    private string $root;
    private string $name;
    private string $path;

    private function __construct(string $root, string $name = '')
    {
        $this->root = $root;
        $this->name = $name;
        $this->path = $name ? $root . DIRECTORY_SEPARATOR . $name : $root;
    }

    /**
     * @param string $path Real, absolute path to existing directory
     */
    public static function root(string $path): ?self
    {
        $path   = rtrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
        $isReal = $path === realpath($path) && is_dir($path);
        return $isReal ? new self($path) : null;
    }

    /**
     * @return string absolute pathname within local filesystem
     */
    public function absolute(): string
    {
        return $this->path;
    }

    /**
     * @return string path name relative to its root directory
     */
    public function relative(): string
    {
        return $this->name;
    }

    /**
     * Either forward `/` or backward `\` slashes are accepted for path
     * separators, and both leading & trailing slashes will be ignored.
     * For either empty or dot-path segments Exception will be thrown.
     *
     * @param string $name Canonical relative pathname for child node. Either
     *
     * @throws InvalidNodeName when name contains empty or dot-path segments
     *
     * @return self with added or expanded relative path
     */
    public function forChildNode(string $name): self
    {
        return new self($this->root, $this->validName($name));
    }

    /**
     * @return string absolute path name of closest existing ancestor node
     */
    public function closestAncestor(): string
    {
        $path = dirname($this->path);
        while (!file_exists($path) && !is_link($path)) {
            $path = dirname($path);
        }
        return $path;
    }

    /**
     * @param callable|null $filter fn(string) => bool
     *
     * @return Iterator
     */
    public function descendantPaths(callable $filter = null): Iterator
    {
        $flags = FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_PATHNAME;
        $nodes = new RecursiveDirectoryIterator($this->path, $flags);
        $nodes = new RecursiveIteratorIterator($nodes, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($nodes as $node) {
            if ($filter && !$filter($node)) { continue; }
            yield new self($this->root, substr($node, strlen($this->root) + 1));
        }
    }

    /**
     * @throws RootDirectoryNotFound for not existing directory
     *
     * @return self without relative path
     */
    public function asRoot(): self
    {
        if (!$this->name) { return $this; }
        if (!is_dir($this->path)) {
            throw RootDirectoryNotFound::forRoot($this->root, $this->name);
        }
        return new self($this->path);
    }

    private function validName(string $name): string
    {
        $name = trim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $name), DIRECTORY_SEPARATOR);
        if (!$name) { throw InvalidNodeName::forEmptyName(); }

        $emptySegment = $this->hasSegment($name, '');
        if ($emptySegment) { throw InvalidNodeName::forEmptySegment($name); }

        $dotSegment = $this->hasSegment($name, '..', '.');
        if ($dotSegment) { throw InvalidNodeName::forDotSegment($name); }

        return $this->name ? $this->name . DIRECTORY_SEPARATOR . $name : $name;
    }

    private function hasSegment(string $name, string ...$segments): bool
    {
        $name = $this->pathFragment($name);
        foreach ($segments as $segment) {
            $fragmentFound = strpos($name, $this->pathFragment($segment)) !== false;
            if ($fragmentFound) { return true; }
        }
        return false;
    }

    private function pathFragment(string $segment): string
    {
        return DIRECTORY_SEPARATOR . $segment . DIRECTORY_SEPARATOR;
    }
}
