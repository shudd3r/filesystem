<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Tests\Doubles\FakeNodes;

use Shudd3r\Filesystem\Tests\Doubles\FakeNodes;
use Shudd3r\Filesystem\Generic\Pathname;
use Shudd3r\Filesystem\Local\LocalNode;


class FakeLocalNodes implements FakeNodes
{
    private Pathname $rootPath;

    public function __construct(Pathname $rootPath)
    {
        $this->rootPath = $rootPath;
    }

    public function node(string $name = '', bool $typeMatch = true): LocalNode
    {
        $pathname = $name ? $this->rootPath->forChildNode($name) : $this->rootPath;

        return new class($pathname, $typeMatch) extends LocalNode {
            private bool $typeMatch;
            private bool $removed = false;

            public function __construct(Pathname $pathname, bool $typeMatch)
            {
                parent::__construct($pathname);
                $this->typeMatch = $typeMatch;
            }

            public function exists(): bool
            {
                if ($this->removed) { return false; }
                $path = $this->pathname->absolute();
                return $this->typeMatch && (file_exists($path) || is_link($path));
            }

            protected function removeNode(): void
            {
                $this->removed = true;
            }
        };
    }
}
