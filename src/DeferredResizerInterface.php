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
     * @param string $targetPath
     *
     * @return DeferredImageInterface
     */
    public function getDeferredImage($targetPath, ImagineInterface $imagine);

    /**
     * Resizes a deferred image.
     *
     * @param string $targetPath
     *
     * @return ImageInterface
     */
    public function resizeDeferredImage(DeferredImageInterface $image);
}
