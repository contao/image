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
    /**
     * Returns the size.
     */
    public function getSize(): BoxInterface;

    /**
     * Returns the relative flag.
     */
    public function isRelative(): bool;

    /**
     * Returns the undefined flag.
     */
    public function isUndefined(): bool;
}
