<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Virtual\Root\TreeNode;

use Shudd3r\Filesystem\Virtual\Root\TreeNode;


class MissingNode extends TreeNode
{
    private Directory $directory;
    private array     $missingSegments;

    public function __construct(Directory $directory, string ...$missingSegments)
    {
        $this->directory       = $directory;
        $this->missingSegments = $missingSegments;
    }

    public function node(string ...$pathSegments): TreeNode
    {
        return new self($this->directory, ...$this->missingSegments, ...$pathSegments);
    }

    public function exists(): bool
    {
        return false;
    }

    public function putContents(string $contents): void
    {
        $this->attachNode(new File($contents));
    }

    public function setTarget(string $path): void
    {
        $this->attachNode(new Link($path));
    }

    public function missingSegments(): array
    {
        return $this->missingSegments;
    }

    private function attachNode(TreeNode $node): void
    {
        $attachName = array_shift($this->missingSegments);
        while ($name = array_pop($this->missingSegments)) {
            $node = new Directory([$name => $node]);
        }
        $this->directory->add($attachName, $node);
    }
}
