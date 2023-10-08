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
    private bool $typeMatch = true;

    public static function fromRootDirectory(Root\TreeNode\Directory $rootDirectory, string $name = ''): self
    {
        $path = $name ? Pathname::root('vfs://')->forChildNode($name) : Pathname::root('vfs://');
        return new self(new Root('vfs://', $rootDirectory), $path);
    }

    public function withTypeMismatch(): self
    {
        $this->typeMatch = false;
        return $this;
    }

    protected function nodeExists(Root\TreeNode $node): bool
    {
        return $node->exists() && $this->typeMatch;
    }
}
