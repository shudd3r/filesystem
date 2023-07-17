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


interface File extends Node
{
    /**
     * @return string Contents of this file or empty string if file does not exist
     */
    public function contents(): string;

    /**
     * Replaces existing file contents with given string or creates new
     * file with it.
     *
     * @param string $contents
     */
    public function write(string $contents): void;

    /**
     * Appends given string to existing file contents or creates new file
     * with it.
     *
     * @param string $contents
     */
    public function append(string $contents): void;
}
