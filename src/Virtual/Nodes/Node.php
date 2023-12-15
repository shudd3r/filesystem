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

use Shudd3r\Filesystem\Generic\Pathname;
use Generator;
use LogicException;


class Node
{
    private Pathname $root;
    private TreeNode $node;
    private string   $path;
    private string   $realPath;

    /**
     * @param Pathname    $root     Filesystem root path
     * @param TreeNode    $node     Found node
     * @param string      $path     Pathname used to find TreeNode relative to filesystem root
     * @param string|null $realPath Resolved path or null if the same as $path
     */
    public function __construct(Pathname $root, TreeNode $node, string $path, string $realPath = null)
    {
        $this->root     = $root;
        $this->node     = $node;
        $this->path     = $path;
        $this->realPath = $realPath ?? $path;
    }

    /**
     * Method used to determine if operation is necessary or possible.
     * For example copying file contents to itself - that is why Node
     * comparison have to resolve nodes accessed through symlinks.
     */
    public function equals(Node $node): bool
    {
        return $this->node->equals($node->node);
    }

    /**
     * Method used to determine the path of closest existing ancestor
     * or leaf node in case of Nodes created with invalid paths.
     * For existing nodes method should return path with unresolved
     * symlinks that was used to access it.
     *
     * @return string Path of existing node with unresolved symlinks
     */
    public function foundPath(): string
    {
        $segments = $this->node->missingSegments();
        $notFound = $segments ? '/' . implode('/', $segments) : '';
        if (!$notFound) { return $this->path; }
        $foundPath = substr($this->path, 0, -strlen($notFound));
        return str_ends_with($foundPath, '/') ? $foundPath . '/' : $foundPath;
    }

    /**
     * Full path with resolved symlinks for both existing and not existing,
     * but valid nodes. For invalid nodes null should be returned.
     *
     * @return string|null Full pathname to valid node with resolved symlinks
     */
    public function resolvedPath(): ?string
    {
        return $this->isValid() ? $this->realPath : null;
    }

    /**
     * For existing symlink that cannot be resolved to existing node method
     * should return false.
     *
     * @see Node::isLink() - use this method to determine stale symlink
     *
     * @return bool true if node exists in filesystem
     */
    public function exists(): bool
    {
        return $this->node->exists();
    }

    /**
     * @return bool true if node is existing directory
     */
    public function isDir(): bool
    {
        return $this->node->isDir();
    }

    /**
     * @return bool true if node is existing file
     */
    public function isFile(): bool
    {
        return $this->node->isFile();
    }

    /**
     * @return bool true if node is existing symlink
     */
    public function isLink(): bool
    {
        return $this->node->isLink();
    }

    /**
     * @return bool true for node with valid path
     */
    public function isValid(): bool
    {
        return $this->node->isValid();
    }

    /**
     * Removes existing node from filesystem.
     *
     * @throws LogicException
     */
    public function remove(): void
    {
        $this->node->remove();
    }

    /**
     * Creates node as directory.
     *
     * @throws LogicException
     */
    public function createDir(): void
    {
        $this->node->createDir();
    }

    /**
     * @return Generator Filenames within directory node
     */
    public function filenames(): Generator
    {
        return $this->node->filenames();
    }

    /**
     * @return string File contents or empty string
     */
    public function contents(): string
    {
        return $this->node->contents();
    }

    /**
     * Writes given contents to file.
     *
     * @throws LogicException
     */
    public function putContents(string $contents): void
    {
        $this->node->putContents($contents);
    }

    /**
     * @return ?string Target pathname of symlink node
     */
    public function target(): ?string
    {
        if (!$this->isLink()) { return null; }
        $target = $this->node->target();
        return $target ? $this->root->forChildNode($target)->absolute() : $this->root->absolute();
    }

    /**
     * Sets given path as Link target.
     *
     * @throws LogicException
     */
    public function setTarget(string $path): void
    {
        $pathname = $this->root->asRootFor($path);
        $this->node->setTarget(str_replace($this->root->separator(), '/', $pathname->relative()));
    }

    /**
     * Moves node to given target Node.
     *
     * @throws LogicException
     */
    public function moveTo(Node $target): void
    {
        $this->node->moveTo($target->node);
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
        return $this->node->isAllowed($access);
    }
}
