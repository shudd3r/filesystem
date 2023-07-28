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

use Shudd3r\Filesystem\Generic\FileIterator;
use Shudd3r\Filesystem\Exception\InvalidNodeName;


interface Files
{
    /**
     * Unless precondition assertion fails, File instance MUST be returned
     * whether file with given name exists within this directory structure
     * or not.
     *
     * Concrete implementations MAY specify different syntax accepted for
     * file name. If phpDoc does not include implementation specific
     * constraints, following rules should be assumed (RECOMMENDED):
     * - Only canonical paths are be allowed,
     * - No empty or dot-path segments,
     * - Path separators are changed to system separators,
     * - Superfluous path separators at both ends of the name are trimmed.
     *
     * @param string $name File basename or relative file pathname
     *
     * @throws InvalidNodeName     when given file name with invalid syntax
     * @throws FilesystemException when asserted preconditions fail
     *
     * @return File
     *
     * @see Node::validated() method for explicit precondition checks and
     * concrete FilesystemException types
     */
    public function file(string $name): File;

    /**
     * @return FileIterator Iterator of all files in directory and its subdirectories
     */
    public function files(): FileIterator;
}
