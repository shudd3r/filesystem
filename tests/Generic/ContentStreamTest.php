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
use RuntimeException;

require_once dirname(__DIR__) . '/Fixtures/native-override/generic.php';


class ContentStreamTest extends TestCase
{
    use Fixtures\TestUtilities;

    public function test_resource_method_returns_wrapped_stream(): void
    {
        $stream = new ContentStream($resource = $this->resource());
        $this->assertSame($resource, $stream->resource());
    }

    public function test_uri_method_returns_resource_uri(): void
    {
        $file   = self::$temp->file('foo/bar.txt');
        $stream = new ContentStream(fopen($file, 'rb'));
        $this->assertSame($file, $stream->uri());
    }

    public function test_cannot_instantiate_with_non_resource_argument(): void
    {
        $this->override('is_resource', false);
        $this->expectException(InvalidArgumentException::class);
        new ContentStream($this->resource());
    }

    public function test_cannot_instantiate_with_not_readable_stream(): void
    {
        $this->override('stream_get_meta_data', ['mode' => 'w']);
        $this->expectException(InvalidArgumentException::class);
        new ContentStream($this->resource());
    }

    public function test_cannot_instantiate_with_non_stream_argument(): void
    {
        $this->override('get_resource_type', 'not-stream');
        $this->expectException(InvalidArgumentException::class);
        new ContentStream($this->resource());
    }

    public function test_resource_is_closed_when_object_is_destroyed(): void
    {
        $stream = new ContentStream($resource = $this->resource());
        $this->assertTrue(is_resource($resource));
        unset($stream);
        $this->assertFalse(is_resource($resource));

        $isResource = function (ContentStream $stream): bool {
            return is_resource($stream->resource());
        };

        $resource = $this->resource();
        $this->assertTrue($isResource(new ContentStream($resource)), 'Resource should be open in function scope');
        $this->assertFalse(is_resource($resource), 'Resource should be closed after function is executed');
    }

    public function test_resource_method_for_resource_closed_in_outside_scope_throws_Exception(): void
    {
        $resource = $this->resource();
        $stream   = new ContentStream($resource);
        fclose($resource);
        $this->expectException(RuntimeException::class);
        $stream->resource();
    }
}
