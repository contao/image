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

interface ImportantPartInterface
{
    /**
     * Returns the relative X position as a fraction.
     */
    public function getX(): float;

    /**
     * Returns the relative Y position as a fraction.
     */
    public function getY(): float;

    /**
     * Returns the relative width as a fraction.
     */
    public function getWidth(): float;

    /**
     * Returns the relative height as a fraction.
     */
    public function getHeight(): float;
}
