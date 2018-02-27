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
use Imagine\Image\PointInterface;

interface ImportantPartInterface
{
    /**
     * Returns the position.
     *
     * @return PointInterface
     */
    public function getPosition();

    /**
     * Returns the size.
     *
     * @return BoxInterface
     */
    public function getSize();
}
