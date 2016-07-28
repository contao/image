<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Resizes Image objects.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
interface ResizerInterface
{
    /**
     * Constructor.
     *
     * @param ResizeCalculatorInterface $calculator The resize calculator object
     * @param Filesystem                $filesystem The filesystem object
     * @param string                    $path       The absolute image assets path
     */
    public function __construct(
        ResizeCalculatorInterface $calculator,
        Filesystem $filesystem,
        $path
    );

    /**
     * Resizes an Image object.
     *
     * @param ImageInterface               $image   The source image
     * @param ResizeConfigurationInterface $config  The resize configuration
     * @param ResizeOptionsInterface       $options The resize options
     *
     * @return ImageInterface The resized image as new object
     */
    public function resize(ImageInterface $image, ResizeConfigurationInterface $config, ResizeOptionsInterface $options);
}
