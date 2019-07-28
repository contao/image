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

interface ResizerInterface
{
    /**
     * Resizes an Image object.
     */
    public function resize(ImageInterface $image, ResizeConfiguration $config, ResizeOptions $options): ImageInterface;
}
