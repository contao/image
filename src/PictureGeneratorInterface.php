<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
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
     * @param ResizerInterface $resizer     The resizer object
     * @param bool             $bypassCache True to bypass the image cache
     */
    public function __construct(ResizerInterface $resizer, $bypassCache);

    /**
     * Generates a Picture object.
     *
     * @param ImageInterface                $image          The Image object
     * @param PictureConfigurationInterface $config         The configuration
     * @param array                         $imagineOptions The options for Imagine save
     *
     * @return PictureInterface The generated Picture object
     */
    public function generate(ImageInterface $image, PictureConfigurationInterface $config, array $imagineOptions = []);
}
