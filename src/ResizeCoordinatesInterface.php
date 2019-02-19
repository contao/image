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

interface ResizeCoordinatesInterface
{
    /**
     * Returns the size.
     */
    public function getSize(): BoxInterface;

    /**
     * Returns the crop start coordinate.
     */
    public function getCropStart(): PointInterface;

    /**
     * Returns the crop size.
     */
    public function getCropSize(): BoxInterface;

    /**
     * Returns a hash of the coordinates.
     */
    public function getHash(): string;

    /**
     * Compares the coordinates with another ResizeCoordinates or Box object.
     *
     * @param ResizeCoordinatesInterface|BoxInterface $coordinates
     */
    public function isEqualTo($coordinates): bool;
}
