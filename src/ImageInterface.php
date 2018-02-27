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

interface ImageInterface
{
    /**
     * Returns the imagine instance.
     *
     * @return ImagineInterface
     */
    public function getImagine();

    /**
     * Returns the path.
     *
     * @return string
     */
    public function getPath();

    /**
     * Returns the URL relative to the specified root directory,
     * optionally prefixed with the specified URL prefix.
     *
     * @param string $rootDir
     * @param string $prefix
     *
     * @return string
     */
    public function getUrl($rootDir, $prefix = '');

    /**
     * Returns the dimensions.
     *
     * @return ImageDimensionsInterface
     */
    public function getDimensions();

    /**
     * Returns the important part.
     *
     * @return ImportantPartInterface
     */
    public function getImportantPart();

    /**
     * Sets the important part.
     *
     * @param ImportantPartInterface|null $importantPart
     *
     * @return self
     */
    public function setImportantPart(ImportantPartInterface $importantPart = null);
}
