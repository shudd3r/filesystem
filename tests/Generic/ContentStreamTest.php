<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Tests\Generic;

use PHPUnit\Framework\TestCase;
use Shudd3r\Filesystem\Generic\ContentStream;
use Shudd3r\Filesystem\Tests\Fixtures;
use InvalidArgumentException;

require_once dirname(__DIR__) . '/Fixtures/native-override/generic.php';


class ContentStreamTest extends TestCase
{
    use Fixtures\TempFilesHandling;

    public function test_resource_method_returns_wrapped_stream(): void
    {
        $resource = fopen(self::$temp->file('foo.txt'), 'r');
        $stream   = new ContentStream($resource);
        $this->assertSame($resource, $stream->resource());
    }

    public function test_cannot_instantiate_with_non_resource_argument(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ContentStream(3);
    }

    public function test_cannot_instantiate_with_not_readable_stream(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ContentStream(fopen(self::$temp->file('foo.txt'), 'w'));
    }

    public function test_cannot_instantiate_with_non_stream_argument(): void
    {
        $this->override('get_resource_type', 'not-stream');
        $this->expectException(InvalidArgumentException::class);
        new ContentStream(fopen(self::$temp->file('foo.txt'), 'r'));
    }
}
