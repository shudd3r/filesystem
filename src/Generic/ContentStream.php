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

use Shudd3r\Filesystem\Exception\IOException\UnableToReadContents;
use InvalidArgumentException;


class ContentStream
{
    /** @var resource */
    private $stream;

    /** @var array{ uri: string } */
    private array $metaData;

    /**
     * @param resource $stream Readable stream resource
     */
    public function __construct($stream)
    {
        $validType = is_resource($stream) && get_resource_type($stream) === 'stream';
        if (!$validType) {
            throw new InvalidArgumentException('Invalid stream resource');
        }

        $metaData   = stream_get_meta_data($stream);
        $mode       = $metaData['mode'];
        $isReadable = $mode[0] === 'r' || strstr($mode, '+');
        if (!$isReadable) {
            throw new InvalidArgumentException('Resource is not readable');
        }

        $this->stream   = $stream;
        $this->metaData = $metaData;
    }

    /**
     * Ensure that resource is closed.
     */
    public function __destruct()
    {
        is_resource($this->stream) && fclose($this->stream);
    }

    /**
     * @throws UnableToReadContents
     *
     * @return resource
     */
    public function resource()
    {
        if (is_resource($this->stream)) { return $this->stream; }
        throw new UnableToReadContents('Stream resource was closed in outside scope');
    }

    /**
     * @throws UnableToReadContents
     *
     * @return string
     */
    public function contents(): string
    {
        $contents = @stream_get_contents($this->resource());
        if ($contents === false) {
            throw UnableToReadContents::fromStream($this);
        }
        return $contents;
    }

    /**
     * @return string URI of wrapped resource
     */
    public function uri(): string
    {
        return $this->metaData['uri'];
    }
}
