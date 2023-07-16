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

use Shudd3r\Filesystem\Exception\InvalidPath;
use Shudd3r\Filesystem\Exception\UnreachablePath;
use Shudd3r\Filesystem\Exception\DirectoryDoesNotExist;


interface Directory
{
    /**
     * @return string Absolute directory pathname
     */
    public function pathname(): string;

    /**
     * File instance MUST be returned regardless if file with given name
     * exists within structure of this root directory or not, unless one
     * of following exceptions occur:
     * - When supplied name is syntactically correct path, but cannot be
     *   resolved in the context of existing directory structure due to
     *   conflicting nodes `UnreachablePath` exception SHOULD be thrown.
     * - For name with invalid syntax specified by concrete implementation
     *   this method MUST throw `InvalidPath` exception.
     *
     * @param string $name File basename or relative file pathname
     *
     * @throws InvalidPath|UnreachablePath
     *
     * @return File
     */
    public function file(string $name): File;

    /**
     * Creates relative directory instance for which all provided names
     * will be prepended with given `$name` as if they were derived from
     * root directory directly.
     *
     * Directory instance MUST be returned regardless if directory with
     * given name exists within structure of this root directory or not,
     * unless one of following exceptions occur:
     * - When supplied name is syntactically correct path, but cannot be
     *   resolved in the context of existing directory structure due to
     *   conflicting nodes `UnreachablePath` exception SHOULD be thrown.
     * - For name with invalid syntax specified by concrete implementation
     *   this method MUST throw `InvalidPath` exception.
     *
     * @param string $name Directory basename or relative directory pathname
     *
     * @throws InvalidPath|UnreachablePath
     *
     * @return self Relative directory instance
     */
    public function subdirectory(string $name): self;

    /**
     * @return Files Iterator of all files in directory and its subdirectories
     */
    public function files(): Files;

    /**
     * Converts relative subdirectory instance to root directory.
     * If current instance is already a root directory same object
     * will be returned back, and if directory does not exist
     * exception will be thrown.
     *
     * @throws DirectoryDoesNotExist
     *
     * @return self Root directory instance
     */
    public function asRoot(): self;
}
