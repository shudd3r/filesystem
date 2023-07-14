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
use Shudd3r\Filesystem\Local\PathName\FileName;


class LocalFile implements File
{
    private string $absolutePath;
    private string $relativePath;

    /**
     * FileName value object ensures that path to file either already
     * exists or is potentially valid (is not currently a directory
     * nor directory symlink).
     *
     * @param FileName $fileName
     */
    public function __construct(FileName $fileName)
    {
        $this->absolutePath = (string) $fileName;
        $this->relativePath = $fileName->name();
    }

    public function pathname(): string
    {
        return $this->absolutePath;
    }

    public function name(): string
    {
        return $this->relativePath;
    }

    public function exists(): bool
    {
        return is_file($this->absolutePath);
    }

    public function contents(): string
    {
        return $this->exists() ? file_get_contents($this->absolutePath) : '';
    }

    public function write(string $contents): void
    {
        file_put_contents($this->absolutePath, $contents);
    }

    public function append(string $contents): void
    {
        file_put_contents($this->absolutePath, $contents, FILE_APPEND);
    }
}
