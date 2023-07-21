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

use Shudd3r\Filesystem\Exception\UnreachablePath;
use Shudd3r\Filesystem\Exception\AccessDenied;


interface Node
{
    public const READ  = 1;
    public const WRITE = 2;

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
     * Returns validated node or throws Exception. This method should allow
     * developers to control when **checked Exceptions** should be thrown
     * and handled.
     *
     * Since not existing nodes might be instantiated, this method will always
     * check for invalid node types or otherwise inaccessible paths.
     * For example: subdirectory instantiated with a file path or node with
     * path that expands through existing file.
     *
     * Node::READ and Node::WRITE flags may be used to assert preconditions
     * for read & write permissions.
     *
     * For more detailed error messages this method MIGHT be implicitly
     * called before each read & write action with corresponding flag.
     *
     * @param int $flags Node::READ|Node::WRITE to assert permissions
     *
     * @throws UnreachablePath|AccessDenied
     *
     * @return self Validated node
     */
    public function validated(int $flags = 0): self;

    /**
     * Removes node and its child nodes from filesystem.
     */
    public function remove(): void;
}
