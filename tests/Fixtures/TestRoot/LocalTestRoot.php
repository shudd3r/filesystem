<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Tests\Fixtures\TestRoot;

use Shudd3r\Filesystem\Tests\Fixtures\TestRoot;
use Shudd3r\Filesystem\Tests\Fixtures\TempFiles;
use Shudd3r\Filesystem\Local\LocalDirectory;
use Shudd3r\Filesystem\Local\LocalNode;
use Shudd3r\Filesystem\Generic\Pathname;
use PHPUnit\Framework\Assert;


class LocalTestRoot extends TestRoot
{
    private TempFiles $temp;

    public function __construct(TempFiles $temp, array $structure = [])
    {
        $path = $temp->directory();
        $this->temp = $temp;
        parent::__construct(LocalDirectory::root($path), Pathname::root($path, DIRECTORY_SEPARATOR));
        $this->createNodes($structure);
    }

    public function node(string $name = '', bool $typeMatch = true): LocalNode
    {
        return new class($this->pathname($name), $typeMatch) extends LocalNode {
            private bool $typeMatch;
            private bool $removed = false;

            public function __construct(Pathname $pathname, bool $typeMatch)
            {
                parent::__construct($pathname);
                $this->typeMatch = $typeMatch;
            }

            public function exists(): bool
            {
                if ($this->removed) { return false; }
                $path = $this->pathname->absolute();
                return $this->typeMatch && (file_exists($path) || is_link($path));
            }

            protected function removeNode(): void
            {
                $this->removed = true;
            }
        };
    }

    public function assertStructure(array $structure, string $message = ''): void
    {
        $rootPath   = $this->rootDir->pathname();
        $rootLength = strlen($rootPath) + 1;

        $tree = [];
        foreach ($this->temp->nodes($rootPath) as $pathname) {
            $path     = str_replace(DIRECTORY_SEPARATOR, '/', substr($pathname, $rootLength));
            $segments = explode('/', $path);
            $leafNode = array_pop($segments);
            $current  = &$tree;
            foreach ($segments as $value) {
                $current[$value] ??= [];
                $current = &$current[$value];
            }
            if (isset($current[$leafNode])) { continue; }
            if (is_dir($pathname) && !is_link($pathname)) {
                $current[$leafNode] = [];
                continue;
            }
            $current[$leafNode] = is_link($pathname)
                ? '@' . str_replace(DIRECTORY_SEPARATOR, '/', substr(readlink($pathname), $rootLength))
                : file_get_contents($pathname);
        }
        Assert::assertEquals($structure, $tree, $message);
    }

    private function createNodes(array $tree, string $path = ''): ?array
    {
        if (!$tree) { $this->temp->directory($path); }

        $links = [];
        foreach ($tree as $name => $value) {
            $name     = $path ? $path . '/' . $name : $name;
            $newLinks = is_array($value) ? $this->createNodes($value, $name) : $this->createLeaf($name, $value);
            $newLinks && $links = array_merge($links, $newLinks);
        }

        if ($path) { return $links; }
        foreach ($links as $name => $value) {
            $this->temp->symlink($value, $name);
        }
        return null;
    }

    private function createLeaf(string $name, string $value): array
    {
        if (str_starts_with($value, '@')) {
            return [$name => substr($value, 1)];
        }
        $this->temp->file($name, $value);
        return [];
    }
}
