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

interface ResizeCoordinatesInterface
{
    /**
     * Returns the size.
     *
     * @return BoxInterface
     */
    public function getSize();

    /**
     * Returns the crop start coordinate.
     *
     * @return PointInterface
     */
    public function getCropStart();

    /**
     * Returns the crop size.
     *
     * @return BoxInterface
     */
    public function getCropSize();

    /**
     * Returns a hash of the coordinates.
     *
     * @return string
     */
    public function getHash();

    /**
     * Compares the coordinates with another ResizeCoordinates or Box object.
     *
     * @param ResizeCoordinatesInterface|BoxInterface $coordinates
     *
     * @return bool
     */
    public function isEqualTo($coordinates);
}
