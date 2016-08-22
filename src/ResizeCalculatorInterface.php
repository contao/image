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
 * Resize calculator interface.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
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
