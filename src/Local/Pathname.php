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

use Shudd3r\Filesystem\Exception\DirectoryDoesNotExist;
use Shudd3r\Filesystem\Exception\InvalidPath;


final class Pathname
{
    private string $root;
    private string $name;

    private function __construct(string $root, string $name = '')
    {
        $this->root = $root;
        $this->name = $name;
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
        return $this->name ? $this->root . DIRECTORY_SEPARATOR . $this->name : $this->root;
    }

    /**
     * @return string path name relative to its root directory
     */
    public function relative(): string
    {
        return $this->name;
    }

    /**
     * Forward and backward slashes at the beginning and the end of name
     * will be silently removed, and either empty or dot path segments are
     * not allowed.
     *
     * @param string $name Child node relative pathname
     *
     * @throws InvalidPath
     *
     * @return self with added or expanded relative path
     */
    public function forChildNode(string $name): self
    {
        return new self($this->root, $this->validName($name));
    }

    /**
     * @throws DirectoryDoesNotExist for not existing directory
     *
     * @return self without relative path
     */
    public function asRoot(): self
    {
        if (!$this->name) { return $this; }
        $pathname = $this->absolute();
        if (!is_dir($pathname)) {
            throw DirectoryDoesNotExist::forRoot($this->root, $this->name);
        }
        return new self($pathname);
    }

    private function validName(string $name): string
    {
        $name = trim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $name), DIRECTORY_SEPARATOR);
        if (!$name) {
            throw new InvalidPath('Empty name for child node');
        }

        if ($this->hasSegment($name, '')) {
            $message = 'Empty path segments not allowed - `%s` given';
            throw new InvalidPath(sprintf($message, $name));
        }

        if ($this->hasSegment($name, '..', '.')) {
            $message = 'Dot segments not allowed - `%s` given';
            throw new InvalidPath(sprintf($message, $name));
        }

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
