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

interface DeferredResizerInterface extends ResizerInterface
{
    /**
     * Get a deferred image object for a not yet resized image.
     *
     * @return DeferredImageInterface
     */
    public function getDeferredImage($targetPath);

    /**
     * Resizes a deferred image.
     *
     * @return ImageInterface
     */
    public function resizeDeferredImage($targetPath, ImagineInterface $imagine);
}
