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
use Imagine\Image\PointInterface;

interface ImportantPartInterface
{
    /**
     * Returns the relative X position.
     */
    public function getX(): float;

    /**
     * Returns the relative Y position.
     */
    public function getY(): float;

    /**
     * Returns the relative width.
     */
    public function getWidth(): float;

    /**
     * Returns the relative height.
     */
    public function getHeight(): float;
}
