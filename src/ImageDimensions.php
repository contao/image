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
use Contao\ImagineSvg\RelativeBoxInterface;
use Contao\ImagineSvg\UndefinedBoxInterface;

/**
 * Image Dimensions.
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
     * @var bool
     */
    private $undefined;

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
    ) {
        if ($relative === null && $size instanceof RelativeBoxInterface) {
            $relative = true;
        }

        if ($undefined === null && $size instanceof UndefinedBoxInterface) {
            $undefined = true;
        }

        $this->size = $size;
        $this->relative = (bool) $relative;
        $this->undefined = (bool) $undefined;
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

    /**
     * Gets the undefined flag.
     *
     * @return bool
     */
    public function isUndefined()
    {
        return $this->undefined;
    }
}
