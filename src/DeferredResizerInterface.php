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
     * Resizes an Image object.
     *
     * @return ImageInterface
     */
    public function resizeDeferredImage($targetPath, ImagineInterface $imagine);
}
