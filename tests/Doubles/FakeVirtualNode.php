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
use Shudd3r\Filesystem\Virtual\NodeData;
use Shudd3r\Filesystem\Pathname;


class FakeVirtualNode extends VirtualNode
{
    public bool $removed = false;

    private bool $exists;

    public function __construct(NodeData $nodes, Pathname $pathname, bool $exists = true)
    {
        parent::__construct($nodes, $pathname);
        $this->exists = $exists;
    }

    public function remove(): void
    {
        parent::remove();
        $this->exists = false;
    }

    protected function nodeExists(NodeData $node): bool
    {
        return $this->exists;
    }
}
