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

interface ImportantPartInterface
{
    /**
     * Returns the position.
     */
    public function getPosition(): PointInterface;

    /**
     * Returns the size.
     */
    public function getSize(): BoxInterface;
}
