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
 * Generates Picture objects
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class PictureGenerator
{
    /**
     * @var Resizer
     */
    private $resizer;

    /**
     * Constructor.
     *
     * @param Resizer $resizer The resizer object
     */
    public function __construct(Resizer $resizer)
    {
        $this->resizer = $resizer;
    }

    /**
     * Generates a Picture object
     *
     * @param Image                $image  The Image object
     * @param PictureConfiguration $config The configuration
     *
     * @return Picture The generated Picture object
     */
    public function generate(Image $image, PictureConfiguration $config)
    {
        // Generate all images via `Resizer` based on the `PictureConfiguration`
        // and store the results in a `Picture` object and return it
    }
}
