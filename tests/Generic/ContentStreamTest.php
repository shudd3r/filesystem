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
use Shudd3r\Filesystem\Exception\IOException;
use Shudd3r\Filesystem\Tests\Fixtures\Override;
use InvalidArgumentException;


class ContentStreamTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__) . '/Fixtures/native-override/generic.php';
    }

    protected function tearDown(): void
    {
        Override::reset();
    }

    public function test_resource_method_returns_wrapped_stream(): void
    {
        $stream = new ContentStream($resource = $this->resource());
        $this->assertSame($resource, $stream->resource());
    }

    public function test_uri_method_returns_resource_uri(): void
    {
        $stream = new ContentStream($this->resource());
        $this->assertSame('php://memory', $stream->uri());
    }

    public function test_cannot_instantiate_with_non_resource_argument(): void
    {
        Override::set('is_resource', false);
        $this->expectException(InvalidArgumentException::class);
        new ContentStream($this->resource());
    }

    public function test_cannot_instantiate_with_not_readable_stream(): void
    {
        Override::set('stream_get_meta_data', ['mode' => 'w']);
        $this->expectException(InvalidArgumentException::class);
        new ContentStream($this->resource());
    }

    public function test_cannot_instantiate_with_non_stream_argument(): void
    {
        Override::set('get_resource_type', 'not-stream');
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
        $stream = new ContentStream($resource = $this->resource());
        fclose($resource);
        $this->expectException(IOException\UnableToReadContents::class);
        $stream->resource();
    }

    public function test_contents_method_returns_stream_contents(): void
    {
        $stream = new ContentStream($this->resource('contents...'));
        $this->assertSame('contents...', $stream->contents());
    }

    public function test_fail_to_read_contents_throws_exception(): void
    {
        $stream = new ContentStream($this->resource('contents...'));
        Override::set('stream_get_contents', false);
        $this->expectException(IOException\UnableToReadContents::class);
        $stream->contents();
    }

    /** @return resource */
    private function resource(string $contents = '')
    {
        $resource = fopen('php://memory', 'rb+');
        fwrite($resource, $contents);
        rewind($resource);
        return $resource;
    }
}
