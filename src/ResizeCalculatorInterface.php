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
 * Calculates image coordinates for resizing Image objects.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
interface ResizeCalculatorInterface
{
    /**
     * Resizes an Image object.
     *
     * @param ResizeConfigurationInterface $config        The resize configuration
     * @param ImageDimensionsInterface     $dimensions    The image dimensions
     * @param ImportantPartInterface|null  $importantPart The important part
     *
     * @return ResizeCoordinatesInterface The resize coordinates
     */
    public function calculate(
        ResizeConfigurationInterface $config,
        ImageDimensionsInterface $dimensions,
        ImportantPartInterface $importantPart = null
    );
}
