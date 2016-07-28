<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image\Picture;

use Contao\Image\Image\ImageInterface;
use Contao\Image\Resize\ResizeOptionsInterface;
use Contao\Image\Resize\ResizerInterface;

/**
 * Picture generator interface.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
interface PictureGeneratorInterface
{
    /**
     * Constructor.
     *
     * @param ResizerInterface $resizer
     */
    public function __construct(ResizerInterface $resizer);

    /**
     * Generates a Picture object.
     *
     * @param ImageInterface                $image
     * @param PictureConfigurationInterface $config
     * @param ResizeOptionsInterface        $options
     *
     * @return PictureInterface
     */
    public function generate(
        ImageInterface $image,
        PictureConfigurationInterface $config,
        ResizeOptionsInterface $options
    );
}
