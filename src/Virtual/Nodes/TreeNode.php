<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Virtual\Nodes;

use LogicException;
use Generator;


abstract class TreeNode
{
    /**
     * Returns existing or not existing Node from directory,
     * invalid Node from leaf nodes or Link with path to
     * resolve.
     */
    public function node(string ...$pathSegments): self
    {
        return new TreeNode\InvalidNode(...$pathSegments);
    }

    /**
     * Method used to determine if operation is necessary or possible.
     * For example copying file contents to itself - that is why TreeNode
     * comparison have to resolve nodes accessed through symlinks.
     */
    public function equals(TreeNode $node): bool
    {
        return $this === $node;
    }

    /**
     * For existing symlink that cannot be resolved to neither file
     * or directory this method should return false.
     *
     * @see TreeNode::isLink() method to determine stale symlink
     *
     * @return bool true if node refers a file or directory
     */
    public function exists(): bool
    {
        return true;
    }

    /**
     * @return bool true if node is a directory
     */
    public function isDir(): bool
    {
        return false;
    }

    /**
     * @return bool true if node is a file
     */
    public function isFile(): bool
    {
        return false;
    }

    /**
     * @return bool true if direct node is a symlink
     */
    public function isLink(): bool
    {
        return false;
    }

    /**
     * @return bool true for node with valid path
     */
    public function isValid(): bool
    {
        return true;
    }

    /**
     * Removes direct node from tree structure.
     * For link resolved nodes only link will be removed.
     *
     * @throws LogicException when direct node does not exist
     */
    public function remove(): void
    {
        throw new LogicException();
    }

    /**
     * Creates node as directory within tree structure.
     *
     * @throws LogicException when node exists
     */
    public function createDir(): void
    {
        throw new LogicException();
    }

    /**
     * @return Generator Filenames within directory node
     */
    public function filenames(): Generator
    {
        yield from [];
    }

    /**
     * @return string File contents or empty string
     */
    public function contents(): string
    {
        return '';
    }

    /**
     * Writes given contents to file.
     *
     * @throws LogicException when file does not exist and cannot be created
     */
    public function putContents(string $contents): void
    {
        throw new LogicException();
    }

    /**
     * @return ?string Target pathname of symlink node
     */
    public function target(): ?string
    {
        return null;
    }

    /**
     * Sets given path as Link target.
     *
     * @throws LogicException when link does not exist and cannot be created
     */
    public function setTarget(string $path): void
    {
        throw new LogicException();
    }

    /**
     * @return string[] Unresolved or not found path segments
     */
    public function missingSegments(): array
    {
        return [];
    }

    /**
     * Moves node to given target Node.
     *
     * @throws LogicException when node does not exist or target cannot be created
     */
    public function moveTo(TreeNode $target): void
    {
        throw new LogicException();
    }

    /**
     * Checks whether Node can be read, written or removed based
     * on given \Shudd3r\Filesystem\Node READ, WRITE or REMOVE
     * binary access flags.
     *
     * @see \Shudd3r\Filesystem\Node
     */
    public function isAllowed(int $access): bool
    {
        return true;
    }

    protected function attachNode(TreeNode $node): void
    {
        throw new LogicException();
    }

    protected function baseNode(?TreeNode $overwrite = null): ?TreeNode
    {
        return $this === $overwrite ? null : $this;
    }
}
