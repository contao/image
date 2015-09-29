<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Image;

use Imagine\Image\BoxInterface;

/**
 * Image Dimensions
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class ImageDimensions
{
    /**
     * @var BoxInterface
     */
    private $size;

    /**
     * @var bool
     */
    private $relative;

    /**
     * Constructor.
     *
     * @param BoxInterface $size     The size
     * @param bool         $relative The relative flag
     */
    public function __construct(
        BoxInterface $size,
        $relative = false
    ) {
        $this->size = $size;
        $this->relative = (bool) $relative;
    }

    /**
     * Gets the size.
     *
     * @return BoxInterface
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Gets the relative flag.
     *
     * @return bool
     */
    public function isRelative()
    {
        return $this->relative;
    }
}
