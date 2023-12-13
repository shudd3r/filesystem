<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Tests\Doubles;

use Shudd3r\Filesystem\Virtual\VirtualNode;
use Shudd3r\Filesystem\Generic\Pathname;
use Shudd3r\Filesystem\Virtual\Root;


class FakeVirtualNode extends VirtualNode
{
    private bool $typeMatch;

    public function __construct(Root $root, Pathname $path, bool $typeMatch = true)
    {
        parent::__construct($root, $path);
        $this->typeMatch = $typeMatch;
    }

    protected function nodeExists(Root\Node $node): bool
    {
        return $node->exists() && $this->typeMatch;
    }
}
