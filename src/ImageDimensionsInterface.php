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

/**
 * Image dimensions interface.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
interface ImageDimensionsInterface
{
    /**
     * Returns the size.
     *
     * @return BoxInterface
     */
    public function getSize();

    /**
     * Returns the relative flag.
     *
     * @return bool
     */
    public function isRelative();

    /**
     * Returns the undefined flag.
     *
     * @return bool
     */
    public function isUndefined();
}
