<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Virtual\Nodes\TreeNode;

use Shudd3r\Filesystem\Virtual\Nodes\TreeNode;


class InvalidNode extends TreeNode
{
    private array $missingSegments;

    /**
     * Subtype indicating unresolvable tree path.
     */
    public function __construct(string ...$missingSegments)
    {
        $this->missingSegments = $missingSegments;
    }

    public function node(string ...$pathSegments): TreeNode
    {
        return new self(...$this->missingSegments, ...$pathSegments);
    }

    public function exists(): bool
    {
        return false;
    }

    public function isValid(): bool
    {
        return false;
    }

    public function missingSegments(): array
    {
        return $this->missingSegments;
    }
}
