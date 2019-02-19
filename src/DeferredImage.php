<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image;

class DeferredImage extends Image implements DeferredImageInterface
{
    /**
     * @param string                   $path
     * @param ImageDimensionsInterface $dimensions
     */
    public function __construct($path, $imagine, ImageDimensionsInterface $dimensions)
    {
        $this->path = (string) $path;
        $this->imagine = $imagine;
        $this->dimensions = $dimensions;
    }
}
