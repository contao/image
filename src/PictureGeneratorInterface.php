<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image;

/**
 * Picture generator interface.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
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
