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
use Shudd3r\Filesystem\Virtual\Nodes\TreeNode\InvalidNode;


abstract class TreeNode
{
    /**
     * Returns existing or not existing Node from directory,
     * invalid Node from leaf nodes or Link with path to
     * resolve.
     */
    public function node(string ...$pathSegments): self
    {
        return new InvalidNode(...$pathSegments);
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
     * For existing symlink that cannot be resolved to existing node method
     * should return false.
     *
     * @see TreeNode::isLink() - use this method to determine stale symlink
     *
     * @return bool true if node exists in filesystem
     */
    public function exists(): bool
    {
        return true;
    }

    /**
     * @return bool true if node is existing directory
     */
    public function isDir(): bool
    {
        return false;
    }

    /**
     * @return bool true if node is existing file
     */
    public function isFile(): bool
    {
        return false;
    }

    /**
     * @return bool true if node is existing symlink
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
     * Removes existing node from tree structure.
     *
     * @throws LogicException
     */
    public function remove(): void
    {
        throw new LogicException();
    }

    /**
     * Creates node as directory within tree structure.
     *
     * @throws LogicException
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
     * @throws LogicException
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
     * @throws LogicException
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
     * @throws LogicException
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

    protected function baseNode(TreeNode $overwrite = null): ?TreeNode
    {
        return $this === $overwrite ? null : $this;
    }
}
