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

class ImportantPart implements ImportantPartInterface
{
    /**
     * @var float
     */
    private $x;

    /**
     * @var float
     */
    private $y;

    /**
     * @var float
     */
    private $width;

    /**
     * @var float
     */
    private $height;

    public function __construct(float $x, float $y, float $width, float $height)
    {
        if ($x < 0 || $x > 1 || $y < 0 || $y > 1 || $width < 0 || $width > 1 || $height < 0 || $height > 1) {
            throw new \InvalidArgumentException('X, Y, width and height must be a float between 0 and 1');
        }
        if ($x + $width > 1) {
            throw new \InvalidArgumentException('The X coordinate plus the width must not be greater than 1');
        }
        if ($y + $height > 1) {
            throw new \InvalidArgumentException('The Y coordinate plus the height must not be greater than 1');
        }

        $this->x = $x;
        $this->y = $y;
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * {@inheritdoc}
     */
    public function getX(): float
    {
        return $this->x;
    }

    /**
     * {@inheritdoc}
     */
    public function getY(): float
    {
        return $this->y;
    }

    /**
     * {@inheritdoc}
     */
    public function getWidth(): float
    {
        return $this->width;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeight(): float
    {
        return $this->height;
    }
}
