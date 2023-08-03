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

    public function __destruct()
    {
        fclose($this->stream);
    }

    /**
     * @return resource
     */
    public function resource()
    {
        return $this->stream;
    }
}
