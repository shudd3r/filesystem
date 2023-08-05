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

use Shudd3r\Filesystem\Tests\Fixtures\Override;

function is_resource($resource): bool
{
    return Override::call('is_resource') ?? \is_resource($resource);
}

function stream_get_meta_data($resource): array
{
    return Override::call('stream_get_meta_data') ?? \stream_get_meta_data($resource);
}

function get_resource_type($resource): string
{
    return Override::call('get_resource_type') ?? \get_resource_type($resource);
}
