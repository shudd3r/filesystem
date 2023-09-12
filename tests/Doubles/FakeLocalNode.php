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

use Shudd3r\Filesystem\Local\LocalNode;
use Shudd3r\Filesystem\Pathname;


class FakeLocalNode extends LocalNode
{
    public bool $removed = false;

    private bool $exists;

    public function __construct(string $root = null, string $name = '', bool $exists = true)
    {
        $pathname = Pathname::root($root ?? __DIR__, DIRECTORY_SEPARATOR);
        parent::__construct($name ? $pathname->forChildNode($name) : $pathname);
        $this->exists = $exists;
    }

    public function exists(): bool
    {
        return $this->exists;
    }

    protected function removeNode(): void
    {
        $this->removed = true;
        $this->exists  = false;
    }
}
