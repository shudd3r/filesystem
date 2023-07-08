<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Local;

use Shudd3r\Filesystem\File;


class LocalFile implements File
{
    private LocalDirectory $rootDirectory;
    private string         $relativePath;

    /**
     * @param LocalDirectory $rootDirectory
     * @param string         $relativePath
     */
    public function __construct(LocalDirectory $rootDirectory, string $relativePath)
    {
        $this->rootDirectory = $rootDirectory;
        $this->relativePath  = $relativePath;
    }

    public function pathname(): string
    {
        return $this->rootDirectory->pathname() . DIRECTORY_SEPARATOR . $this->relativePath;
    }

    public function name(): string
    {
        return $this->relativePath;
    }

    public function exists(): bool
    {
        return is_file($this->pathname());
    }

    public function contents(): string
    {
        return $this->exists() ? file_get_contents($this->pathname()) : '';
    }
}
