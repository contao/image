<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image;

interface ResizeCalculatorInterface
{
    /**
     * Resizes an Image object.
     *
     * @param ResizeConfigurationInterface $config
     * @param ImageDimensionsInterface     $dimensions
     * @param ImportantPartInterface|null  $importantPart
     *
     * @return ResizeCoordinatesInterface
     */
    public function calculate(ResizeConfigurationInterface $config, ImageDimensionsInterface $dimensions, ImportantPartInterface $importantPart = null);
}
