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

use Shudd3r\Filesystem\Exception;


trait PathValidation
{
    private function verifyPath(Pathname $pathname, int $flags, bool $forFile): void
    {
        $path = $this->validPath($pathname, $forFile);
        if ($flags & self::READ && !is_readable($path)) {
            throw Exception\AccessDenied::forRead($pathname->relative(), $path, $forFile);
        }

        if ($flags & self::WRITE && !is_writable($path)) {
            throw Exception\AccessDenied::forWrite($pathname->relative(), $path, $forFile);
        }
    }

    private function validPath(Pathname $pathname, bool $forFile): string
    {
        $path = $pathname->absolute();
        if ($forFile ? is_file($path) : is_dir($path)) { return $path; }

        $typeMismatch = file_exists($path) || is_link($path);
        $ancestorPath = $pathname->closestAncestor();
        if ($typeMismatch || !is_dir($ancestorPath)) {
            $invalidPath = file_exists($path) ? $path : $ancestorPath;
            throw Exception\UnreachablePath::for($pathname->relative(), $invalidPath, $forFile);
        }

        return $ancestorPath;
    }
}
