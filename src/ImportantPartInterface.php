<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image;

use Imagine\Image\BoxInterface;
use Imagine\Image\PointInterface;

interface ImportantPartInterface
{
    /**
     * Returns the position.
     *
     * @return PointInterface
     */
    public function getPosition();

    /**
     * Returns the size.
     *
     * @return BoxInterface
     */
    public function getSize();
}
