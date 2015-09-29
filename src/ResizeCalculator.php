<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Image;

/**
 * Calculates image coordinates for resizing Image objects
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class ResizeCalculator
{
    /**
     * Resizes an Image object
     *
     * @param ResizeConfiguration $config        The resize configuration
     * @param ImageDimensions     $dimensions    The image dimensions
     * @param ImportantPart       $importantPart The important part
     *
     * @return ResizeCoordinates The resize coordinates
     */
    public function calculate(
        ResizeConfiguration $config,
        ImageDimensions $dimensions,
        ImportantPart $importantPart
    ) {
        // Calculate the `ResizeCoordinates` for the specified parameters
    }
}
