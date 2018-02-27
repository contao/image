<?php

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
     *
     * @param ImageInterface                $image
     * @param PictureConfigurationInterface $config
     * @param ResizeOptionsInterface        $options
     *
     * @return PictureInterface
     */
    public function generate(ImageInterface $image, PictureConfigurationInterface $config, ResizeOptionsInterface $options);
}
