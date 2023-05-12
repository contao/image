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

use Imagine\Image\ImagineInterface;

interface DeferredResizerInterface extends ResizerInterface
{
    /**
     * Gets a deferred image object for a not yet resized image.
     */
    public function getDeferredImage(string $targetPath, ImagineInterface $imagine): DeferredImageInterface|null;

    public function resizeDeferredImage(DeferredImageInterface $image, bool $blocking = true): ImageInterface|null;
}
