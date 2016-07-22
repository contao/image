<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image;

use Imagine\Image\BoxInterface;

/**
 * Image Dimensions.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
interface ImageDimensionsInterface
{
    /**
     * Constructor.
     *
     * @param BoxInterface $size      The size
     * @param bool|null    $relative  The relative flag
     * @param bool|null    $undefined The undefined flag
     */
    public function __construct(
        BoxInterface $size,
        $relative = null,
        $undefined = null
    );

    /**
     * Gets the size.
     *
     * @return BoxInterface
     */
    public function getSize();

    /**
     * Gets the relative flag.
     *
     * @return bool
     */
    public function isRelative();

    /**
     * Gets the undefined flag.
     *
     * @return bool
     */
    public function isUndefined();
}
