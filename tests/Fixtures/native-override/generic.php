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

function get_resource_type($resource): string
{
    return Override::call('get_resource_type', $resource) ?? \get_resource_type($resource);
}
