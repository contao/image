<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image;

use Imagine\Image\BoxInterface;
use Imagine\Image\PointInterface;

/**
 * Resize coordinates interface.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
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
