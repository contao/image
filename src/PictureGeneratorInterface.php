<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image;

/**
 * Generates Picture objects.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
interface PictureGeneratorInterface
{
    /**
     * Constructor.
     *
     * @param ResizerInterface $resizer The resizer object
     */
    public function __construct(ResizerInterface $resizer);

    /**
     * Generates a Picture object.
     *
     * @param ImageInterface                $image   The Image object
     * @param PictureConfigurationInterface $config  The configuration
     * @param ResizeOptionsInterface        $options The options for Resizer resize
     *
     * @return PictureInterface The generated Picture object
     */
    public function generate(ImageInterface $image, PictureConfigurationInterface $config, ResizeOptionsInterface $options);
}
