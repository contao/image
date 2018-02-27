<?php

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
     *
     * @return BoxInterface
     */
    public function getSize();

    /**
     * Returns the relative flag.
     *
     * @return bool
     */
    public function isRelative();

    /**
     * Returns the undefined flag.
     *
     * @return bool
     */
    public function isUndefined();
}
