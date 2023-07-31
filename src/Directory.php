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

use Shudd3r\Filesystem\Exception\InvalidNodeName;
use Shudd3r\Filesystem\Exception\RootDirectoryNotFound;


interface Directory extends Files, Node
{
    /**
     * Unless precondition assertion fails, Relative Directory instance
     * MUST be returned whether directory with given name exists within
     * this directory structure or not.
     *
     * Relative directory allows iterating child node objects, but their
     * names will remain relative to root directory. Also nodes created
     * from this directory, despite name argument being relative to its
     * pathname will be instantiated with names relative to root directory.
     *
     * For invalid subdirectory name syntax Exception is thrown.
     *
     * Concrete implementations MAY specify different syntax accepted for
     * subdirectory name. If phpDoc does not include implementation specific
     * constraints, following rules should be assumed (RECOMMENDED):
     * - Only canonical paths are allowed (no empty or dot-path segments),
     * - Either forward `/` or backward `\` slash separators are accepted,
     * - Both leading and trailing separators are ignored.
     *
     * @param string $name Directory basename or relative directory pathname
     *
     * @throws InvalidNodeName     when given subdirectory name with invalid syntax
     * @throws FilesystemException when asserted precondition fails
     *
     * @return self Relative directory instance
     *
     * @see self::asRoot() method converting relative subdirectory to root
     * directory
     * @see Node::validated() method for explicit precondition checks and
     * concrete FilesystemException types
     */
    public function subdirectory(string $name): self;

    /**
     * Unless precondition assertion fails, Link instance MUST be returned
     * whether link with given name exists within this directory structure
     * or not.
     *
     * Concrete implementations MAY specify different syntax accepted for
     * link name. If phpDoc does not include implementation specific
     * constraints, following rules should be assumed (RECOMMENDED):
     * - Only canonical paths are allowed (no empty or dot-path segments),
     * - Either forward `/` or backward `\` slash separators are accepted,
     * - Both leading and trailing separators are ignored.
     *
     * @param string $name Link basename or relative link pathname
     *
     * @throws InvalidNodeName     when given link name with invalid syntax
     * @throws FilesystemException when asserted precondition fails
     *
     * @return Link
     *
     * @see Node::validated() method for explicit precondition checks and
     * concrete FilesystemException types
     */
    public function link(string $name): Link;

    /**
     * Converts relative instance for existing directory to root directory.
     * For root directory instances same object is returned.
     *
     * @throws RootDirectoryNotFound when directory does not exist
     *
     * @return self Root directory instance
     *
     * @see self::subdirectory() method creating relative directory
     */
    public function asRoot(): self;
}
