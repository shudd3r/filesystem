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
            throw Exception\FailedPermissionCheck::forRead($pathname->relative(), $path, $forFile);
        }

        if ($flags & self::WRITE && !is_writable($path)) {
            throw Exception\FailedPermissionCheck::forWrite($pathname->relative(), $path, $forFile);
        }

        if ($flags & self::REMOVE) { $this->verifyRemove($pathname, $forFile); }
    }

    private function validPath(Pathname $pathname, bool $forFile): string
    {
        $path = $pathname->absolute();
        if ($forFile ? is_file($path) : is_dir($path)) { return $path; }

        if (file_exists($path) || is_link($path)) {
            throw Exception\UnexpectedNodeType::for($pathname->relative(), $path, $forFile);
        }

        $ancestorPath = $pathname->closestAncestor();
        if (!is_dir($ancestorPath)) {
            throw Exception\UnexpectedLeafNode::for($pathname->relative(), $ancestorPath);
        }

        return $ancestorPath;
    }

    private function verifyRemove(Pathname $pathname, bool $forFile): void
    {
        if (!$pathname->relative()) {
            throw Exception\FailedPermissionCheck::forRootRemove($pathname->absolute());
        }

        $path = $pathname->closestAncestor();
        if (!is_writable($path)) {
            throw Exception\FailedPermissionCheck::forRemove($pathname->relative(), $path, $forFile);
        }
    }
}
