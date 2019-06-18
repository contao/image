<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image;

use Imagine\Image\BoxInterface;

interface ImageDimensionsInterface
{
    public const ORIENTATION_NORMAL = 1;
    public const ORIENTATION_NORMAL_90 = 6;
    public const ORIENTATION_NORMAL_180 = 3;
    public const ORIENTATION_NORMAL_270 = 8;
    public const ORIENTATION_MIRROR = 2;
    public const ORIENTATION_MIRROR_90 = 7;
    public const ORIENTATION_MIRROR_180 = 4;
    public const ORIENTATION_MIRROR_270 = 5;

    /**
     * Returns the size.
     */
    public function getSize(): BoxInterface;

    /**
     * Returns the orientation flag.
     */
    public function getOrientation(): int;

    /**
     * Returns the relative flag.
     */
    public function isRelative(): bool;

    /**
     * Returns the undefined flag.
     */
    public function isUndefined(): bool;
}
