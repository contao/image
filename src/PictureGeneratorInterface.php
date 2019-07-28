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

interface PictureGeneratorInterface
{
    /**
     * Generates a Picture object.
     */
    public function generate(ImageInterface $image, PictureConfiguration $config, ResizeOptions $options): PictureInterface;
}
