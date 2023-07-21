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


interface Node
{
    /**
     * @return string Absolute pathname within filesystem
     */
    public function pathname(): string;

    /**
     * @return string Pathname relative to its root directory, and empty
     *                string for root directory node
     */
    public function name(): string;

    /**
     * @return bool True if node exists
     */
    public function exists(): bool;

    /**
     * Attempt to read node that is not readable SHOULD return empty string
     * or empty collection instead of throwing Exception.
     *
     * @return bool True if node contents or its child nodes can be read
     */
    public function isReadable(): bool;

    /**
     * Attempt to create, modify or remove not writable node MUST throw
     * Exception.
     *
     * @return bool True if node can be created, modified or removed
     */
    public function isWritable(): bool;

    /**
     * Removes node and its child nodes from filesystem.
     */
    public function remove(): void;
}
