<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem;

use Shudd3r\Filesystem\Exception\InvalidNodeName;


final class Pathname
{
    private string $path;
    private string $separator;
    private int    $nameLength;

    private function __construct(string $path, string $separator, int $nameLength = 0)
    {
        $this->path       = $path;
        $this->separator  = $separator;
        $this->nameLength = $nameLength;
    }

    /**
     * @param string $path      absolute directory path
     * @param string $separator directory separator
     */
    public static function root(string $path, string $separator = '/'): self
    {
        return new self($path, $separator);
    }

    /**
     * @return string absolute pathname within filesystem
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
        return $this->nameLength ? substr($this->path, -$this->nameLength) : '';
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
        $name = $this->validName($name);
        $add  = $this->nameLength ? $this->nameLength + strlen($this->separator) : 0;
        $rs   = !$add && str_ends_with($this->path, $this->separator) ? '' : $this->separator;
        return new self($this->path . $rs . $name, $this->separator, $add + strlen($name));
    }

    /**
     * @return self without relative path
     */
    public function asRoot(): self
    {
        return $this->nameLength ? new self($this->path, $this->separator) : $this;
    }

    private function validName(string $name): string
    {
        $name = trim(str_replace(['\\', '/'], $this->separator, $name), $this->separator);
        if (!$name) { throw InvalidNodeName::forEmptyName(); }

        $emptySegment = $this->hasSegment($name, '');
        if ($emptySegment) { throw InvalidNodeName::forEmptySegment($name); }

        $dotSegment = $this->hasSegment($name, '..', '.');
        if ($dotSegment) { throw InvalidNodeName::forDotSegment($name); }

        return $name;
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
        return $this->separator . $segment . $this->separator;
    }
}
