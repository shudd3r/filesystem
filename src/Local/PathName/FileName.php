<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Filesystem package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Filesystem\Local\PathName;

use Shudd3r\Filesystem\Local\Pathname;


/**
 * Value Object which ensures that file of this name either exists or can
 * be created with adequate access permissions.
 *
 * This subtype can be instantiated only with `DirectoryName::file()` method
 * that derives it from existing directory root path.
 */
class FileName extends Pathname
{
}
