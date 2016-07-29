<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image;

use Imagine\Image\BoxInterface;
use Contao\ImagineSvg\RelativeBoxInterface;
use Contao\ImagineSvg\UndefinedBoxInterface;

/**
 * Image dimensions class.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class ImageDimensions implements ImageDimensionsInterface
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
     * {@inheritdoc}
     */
    public function __construct(BoxInterface $size, $relative = null, $undefined = null)
    {
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
     * {@inheritdoc}
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * {@inheritdoc}
     */
    public function isRelative()
    {
        return $this->relative;
    }

    /**
     * {@inheritdoc}
     */
    public function isUndefined()
    {
        return $this->undefined;
    }
}
