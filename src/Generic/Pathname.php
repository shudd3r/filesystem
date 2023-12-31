<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Generic;

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
     * @param string $path      Absolute directory path
     * @param string $separator Directory separator
     */
    public static function root(string $path, string $separator = '/'): self
    {
        return new self($path, $separator);
    }

    /**
     * @return string Absolute pathname within filesystem
     */
    public function absolute(): string
    {
        return $this->path;
    }

    /**
     * @return string Path name relative to its root directory
     */
    public function relative(): string
    {
        return $this->nameLength ? substr($this->path, -$this->nameLength) : '';
    }

    /**
     * @return string Separator of path segments used by this instance
     */
    public function separator(): string
    {
        return $this->separator;
    }

    /**
     * Either forward `/` or backward `\` slashes are accepted as path
     * separators, and both leading & trailing slashes will be ignored.
     * For either empty or dot-path segments Exception will be thrown.
     *
     * @param string $name Canonical relative pathname for child node
     *
     * @throws InvalidNodeName when name contains empty or dot-path segments
     *
     * @return self Instance with added or expanded relative path
     */
    public function forChildNode(string $name): self
    {
        $name = $this->validName($name);
        $add  = $this->nameLength ? $this->nameLength + strlen($this->separator) : 0;
        $rs   = !$add && str_ends_with($this->path, $this->separator) ? '' : $this->separator;
        return new self($this->path . $rs . $name, $this->separator, $add + strlen($name));
    }

    /**
     * Creates Pathname with instance absolute path, but without
     * relative part. If current instance does not contain relative
     * path same instance is returned.
     */
    public function asRoot(): self
    {
        return $this->nameLength ? new self($this->path, $this->separator) : $this;
    }

    /**
     * Creates Pathname with absolute path given as argument relative to
     * instance's absolute path. This is a shorthand method that determines
     * part of absolute pathname string as a relative name that would be
     * used with self::forChildNode() method.
     * If given path does not strictly match current absolute path null will
     * be returned.
     *
     * @param string $absolutePath Absolute path expanding current pathname
     */
    public function asRootFor(string $absolutePath): ?self
    {
        if (!str_starts_with($absolutePath, $this->path)) { return null; }
        $name = substr($absolutePath, strlen($this->path));
        return $name ? $this->asRoot()->forChildNode($name) : $this->asRoot();
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
