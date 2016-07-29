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
 * Picture element interface.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
interface PictureInterface
{
    /**
     * Constructor.
     *
     * @param array $img
     * @param array $sources
     */
    public function __construct(array $img, array $sources);

    /**
     * Returns the image tag attributes.
     *
     * @param string|null $rootDir
     *
     * @return array
     */
    public function getImg($rootDir = null);

    /**
     * Returns the source tags attributes.
     *
     * @param string|null $rootDir
     *
     * @return array
     */
    public function getSources($rootDir = null);
}
