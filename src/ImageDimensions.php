<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image;

use Contao\ImagineSvg\RelativeBoxInterface;
use Contao\ImagineSvg\UndefinedBoxInterface;
use Imagine\Image\BoxInterface;

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
     * @param BoxInterface $size
     * @param bool|null    $relative
     * @param bool|null    $undefined
     */
    public function __construct(BoxInterface $size, $relative = null, $undefined = null)
    {
        if (null === $relative && $size instanceof RelativeBoxInterface) {
            $relative = true;
        }

        if (null === $undefined && $size instanceof UndefinedBoxInterface) {
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
