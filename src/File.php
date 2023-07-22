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

use Shudd3r\Filesystem\Exception\UnexpectedNodeType;
use Shudd3r\Filesystem\Exception\UnexpectedLeafNode;
use Shudd3r\Filesystem\Exception\FailedPermissionCheck;


interface File extends Node
{
    /**
     * @throws UnexpectedNodeType|UnexpectedLeafNode|FailedPermissionCheck
     *
     * @return string Contents of this file or empty string if file does not exist
     */
    public function contents(): string;

    /**
     * Replaces existing file contents with given string or creates new
     * file with it.
     *
     * @param string $contents
     *
     * @throws UnexpectedNodeType|UnexpectedLeafNode|FailedPermissionCheck
     */
    public function write(string $contents): void;

    /**
     * Appends given string to existing file contents or creates new file
     * with it.
     *
     * @param string $contents
     *
     * @throws UnexpectedNodeType|UnexpectedLeafNode|FailedPermissionCheck
     */
    public function append(string $contents): void;
}
