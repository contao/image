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
    public function __construct($path, ImageDimensionsInterface $dimensions)
    {
        $this->path = $path;
        $this->dimensions = $dimensions;
    }

    /**
     * {@inheritdoc}
     */
    public function getImagine()
    {
        throw new \RuntimeException('Cannot get Imagine for deferred image.');
    }
}
