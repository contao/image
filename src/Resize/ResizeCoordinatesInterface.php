<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image\Resize;

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
     * Constructor.
     *
     * @param BoxInterface   $size
     * @param PointInterface $cropStart
     * @param BoxInterface   $cropSize
     */
    public function __construct(BoxInterface $size, PointInterface $cropStart, BoxInterface $cropSize);

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
