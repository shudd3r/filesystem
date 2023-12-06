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

use Shudd3r\Filesystem\Exception\NodeNotFound;
use Shudd3r\Filesystem\Exception\UnexpectedNodeType;
use Shudd3r\Filesystem\Exception\UnexpectedLeafNode;
use Shudd3r\Filesystem\Exception\PermissionDenied;
use Shudd3r\Filesystem\Exception as FilesystemException;


interface Node
{
    public const PATH   = 0;
    public const READ   = 1;
    public const WRITE  = 2;
    public const REMOVE = 4;
    public const EXISTS = 8;

    /**
     * @return string Absolute filesystem pathname using platform-specific
     *                path separators
     */
    public function pathname(): string;

    /**
     * Name is a normalized path relative to root directory, and for root
     * directory nodes empty string is returned. It's path separators are
     * normalized to forward slash `/`, and neither leading nor trailing
     * separators are present.
     *
     * @return string Normalized node pathname relative to its root directory
     */
    public function name(): string;

    /**
     * @return bool True if node exists
     */
    public function exists(): bool;

    /**
     * Attempt to read not readable node SHOULD throw Exception.
     *
     * @return bool True if node contents or its child nodes can be read
     */
    public function isReadable(): bool;

    /**
     * Attempt to create or modify not writable node MUST throw Exception.
     *
     * @return bool True if node can be created, modified or removed
     */
    public function isWritable(): bool;

    /**
     * Attempt to remove not removable node MUST throw Exception unless it
     * doesn't exist.
     *
     * @return bool True if node can be removed
     */
    public function isRemovable(): bool;

    /**
     * Returns validated node or throws Exception. This method should allow
     * developers to control when **checked Exceptions** should be thrown
     * and handled.
     *
     * Since not existing nodes might be instantiated, this method will always
     * check for invalid node types or otherwise inaccessible paths (Node::PATH).
     * For example: subdirectory instantiated with a file path or node with
     * path that expands through existing file.
     *
     * Following flags may be used to assert preconditions:
     * - Node::READ
     * - Node::WRITE
     * - Node::REMOVE
     * - Node::EXISTS
     *
     * For more detailed error messages this method MIGHT be implicitly called
     * before each read, write or remove action with corresponding flag.
     *
     * @param int $flags Additional precondition checks
     *
     * @throws UnexpectedNodeType when different node type with given name exists
     * @throws UnexpectedLeafNode when file (or file link) exists on node's path
     * @throws PermissionDenied   when asserted permissions are denied
     * @throws NodeNotFound       when asserted node does not exist
     *
     * @return self Validated node
     */
    public function validated(int $flags = self::PATH): self;

    /**
     * Removes node and its child nodes from filesystem.
     *
     * Removing node from directory without write permissions or root node
     * itself is not allowed and Exception will be thrown.
     *
     * @throws FilesystemException
     */
    public function remove(): void;
}
