<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image;

use Imagine\Image\ImagineInterface;
use Symfony\Component\Filesystem\Filesystem;

class DeferredImage extends Image implements DeferredImageInterface
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var ImageDimensionsInterface
     */
    private $dimensions;

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
    public function getPath()
    {
        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function getDimensions()
    {
        return $this->dimensions;
    }

    /**
     * {@inheritdoc}
     */
    public function getImagine()
    {
        throw new \RuntimeException('Cannot get Imagine for deferred image.');
    }
}
