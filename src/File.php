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


interface File
{
    /**
     * @return string Absolute file pathname
     */
    public function pathname(): string;

    /**
     * @return string Path name relative to its root directory
     */
    public function name(): string;

    /**
     * @return bool True if file or symlink to file exists
     */
    public function exists(): bool;

    /**
     * @return string Contents of this file or empty string if file does not exist
     */
    public function contents(): string;

    /**
     * Creates file with given contents or replaces contents of existing file.
     *
     * @param string $contents
     */
    public function write(string $contents): void;
}
