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

use Shudd3r\Filesystem\Exception as FilesystemException;


interface Link extends Node
{
    /**
     * @param bool $showRemoved Whether path to removed target node should
     *                          be returned
     *
     * @return ?string Absolute filesystem pathname to linked target using
     *                 platform-specific path separators.
     *                 For not existing link null is returned, and for stale
     *                 link either null or a path to removed node depending
     *                 on $showRemoved argument.
     */
    public function target(bool $showRemoved = false): ?string;

    /**
     * Creates a link to given node or changes target for existing link.
     * Node MUST exist in filesystem, and in case of target change it MUST
     * be the same type as replaced target node. Replacing file link with
     * directory link directly will throw Exception.
     *
     * @param Node $node
     *
     * @throws FilesystemException
     */
    public function setTarget(Node $node): void;

    /**
     * @return bool True if link target is a directory
     */
    public function isDirectory(): bool;

    /**
     * @return bool True if link target is a file
     */
    public function isFile(): bool;
}
