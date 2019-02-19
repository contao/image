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

class DeferredImage extends Image implements DeferredImageInterface
{
    public function __construct(string $path, ImagineInterface $imagine, ImageDimensionsInterface $dimensions)
    {
        $this->path = $path;
        $this->imagine = $imagine;
        $this->dimensions = $dimensions;
    }
}
