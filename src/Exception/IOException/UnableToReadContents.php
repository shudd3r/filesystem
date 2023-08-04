<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Exception\IOException;

use Shudd3r\Filesystem\Exception\IOException;
use Shudd3r\Filesystem\File;
use Shudd3r\Filesystem\Generic\ContentStream;


class UnableToReadContents extends IOException
{
    public static function fromFile(File $file): self
    {
        $message = 'Could not read `%s` file contents in `%s`';
        return new self(sprintf($message, $file->name(), self::nodeRoot($file)));
    }

    public static function fromStream(ContentStream $stream): self
    {
        $metaData = stream_get_meta_data($stream->resource());
        $message  = 'Could not read `%s` stream contents';
        return new self(sprintf($message, $metaData['uri']));
    }
}
