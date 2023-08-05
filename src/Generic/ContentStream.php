<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Generic;

use InvalidArgumentException;
use RuntimeException;


class ContentStream
{
    /** @var resource */
    private $stream;

    /**
     * @param resource $stream Readable stream resource
     */
    public function __construct($stream)
    {
        $validType = is_resource($stream) && get_resource_type($stream) === 'stream';
        if (!$validType) {
            throw new InvalidArgumentException('Invalid stream resource');
        }

        $mode       = stream_get_meta_data($stream)['mode'];
        $isReadable = $mode[0] === 'r' || strstr($mode, '+');
        if (!$isReadable) {
            throw new InvalidArgumentException('Resource is not readable');
        }

        $this->stream = $stream;
    }

    /**
     * Ensure that resource is closed.
     */
    public function __destruct()
    {
        is_resource($this->stream) && fclose($this->stream);
    }

    /**
     * @return resource
     */
    public function resource()
    {
        if (is_resource($this->stream)) { return $this->stream; }
        throw new RuntimeException('Stream resource was closed in outside scope');
    }
}