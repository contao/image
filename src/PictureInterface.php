<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2018 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image;

interface PictureInterface
{
    /**
     * Returns the image tag attributes.
     *
     * @param string|null $rootDir
     * @param string      $prefix
     *
     * @return array
     */
    public function getImg($rootDir = null, $prefix = '');

    /**
     * Returns the source tags attributes.
     *
     * @param string|null $rootDir
     * @param string      $prefix
     *
     * @return array
     */
    public function getSources($rootDir = null, $prefix = '');
}
