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

use Shudd3r\Filesystem\Generic\ContentStream;


interface File extends Node
{
    /**
     *@throws FilesystemException
     *
     * @return string Contents of this file or empty string if file does
     *                not exist
     */
    public function contents(): string;

    /**
     * Replaces existing file contents with given string or creates new
     * file with it.
     *
     * @param string $contents
     *
     * @throws FilesystemException
     */
    public function write(string $contents): void;

    /**
     * Replaces existing file contents with contents of given stream or
     * creates new file with it.
     *
     * @param ContentStream $stream
     *
     * @throws FilesystemException
     */
    public function writeStream(ContentStream $stream): void;

    /**
     * Appends given string to existing file contents or creates new file
     * with it.
     *
     * @param string $contents
     *
     * @throws FilesystemException
     */
    public function append(string $contents): void;

    /**
     * Copies contents from given File.
     *
     * @param File $file
     *
     * @throws FilesystemException
     */
    public function copy(File $file): void;

    /**
     * In situations where required contents are not processed but only
     * transferred, implementations MAY prefer to use (provide or require)
     * ContentStream for optimized performance. If this method returns
     * null string contents SHOULD be used.
     *
     * @return ?ContentStream
     */
    public function contentStream(): ?ContentStream;
}
