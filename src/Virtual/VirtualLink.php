<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Virtual;


class VirtualLink extends VirtualNode
{
    public function target(bool $showRemoved = false): ?string
    {
        return $this->nodes->targetOf($this, $showRemoved);
    }
}
