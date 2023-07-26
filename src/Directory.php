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


interface Directory extends Node
{
    /**
     * File instance MUST be returned regardless if file with given name exists
     * within structure of root directory or not.
     *
     * Concrete implementations MAY specify different syntax accepted for file
     * name. If phpDoc does not include implementation specific constraints,
     * following RECOMMENDED rules should be assumed:
     * - Only canonical paths are be allowed,
     * - No empty or dot-path segments,
     * - Path separators are changed to system separators,
     * - Superfluous path separators at both ends of the name are trimmed.
     *
     * @param string $name File basename or relative file pathname
     *
     * @throws InvalidNodeName when given file name with invalid syntax
     *
     * @return File
     */
    public function file(string $name): File;

    /**
     * Relative directory instance MUST be returned regardless if directory
     * with given name exists within structure of its root directory or not.
     *
     * Relative directory allows iterating child node objects, but their names
     * will remain relative to root directory. Also nodes created from this
     * directory, despite name argument being relative to its pathname will
     * be instantiated with names relative to root directory.
     *
     * For invalid subdirectory name syntax Exception is thrown.
     *
     * Concrete implementations MAY specify different syntax accepted for
     * subdirectory name. If phpDoc does not include implementation specific
     * constraints, following RECOMMENDED rules should be assumed:
     * - Only canonical paths are be allowed,
     * - No empty or dot-path segments,
     * - Path separators are changed to system separators,
     * - Superfluous path separators at both ends of the name are trimmed.
     *
     * @param string $name Directory basename or relative directory pathname
     *
     * @throws InvalidNodeName when given subdirectory name with invalid syntax
     *
     * @return self Relative directory instance
     *
     * @see self::asRoot() method converting relative subdirectory to root
     * directory
     */
    public function subdirectory(string $name): self;

    /**
     * @return Files Iterator of all files in directory and its subdirectories
     */
    public function files(): Files;

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
