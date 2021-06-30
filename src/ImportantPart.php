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

use Contao\Image\Exception\CoordinatesOutOfBoundsException;

class ImportantPart
{
    private const ROUNDING_ERROR_THRESHOLD = 1 / 100000;

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

    public function __construct(float $x = 0, float $y = 0, float $width = 1, float $height = 1)
    {
        if ($x < 0 || $x > 1 || $y < 0 || $y > 1 || $width < 0 || $width > 1 || $height < 0 || $height > 1) {
            throw new CoordinatesOutOfBoundsException('X, Y, width and height must be a float between 0 and 1');
        }

        if ($x + $width > 1) {
            if ($x + $width - 1 > self::ROUNDING_ERROR_THRESHOLD) {
                throw new CoordinatesOutOfBoundsException('The X coordinate plus the width must not be greater than 1');
            }

            $width = 1 - $x;
        }

        if ($y + $height > 1) {
            if ($y + $height - 1 > self::ROUNDING_ERROR_THRESHOLD) {
                throw new CoordinatesOutOfBoundsException('The Y coordinate plus the height must not be greater than 1');
            }

            $height = 1 - $y;
        }

        $this->x = $x;
        $this->y = $y;
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * Returns the relative X position as a fraction.
     */
    public function getX(): float
    {
        return $this->x;
    }

    /**
     * Returns the relative Y position as a fraction.
     */
    public function getY(): float
    {
        return $this->y;
    }

    /**
     * Returns the relative width as a fraction.
     */
    public function getWidth(): float
    {
        return $this->width;
    }

    /**
     * Returns the relative height as a fraction.
     */
    public function getHeight(): float
    {
        return $this->height;
    }
}
