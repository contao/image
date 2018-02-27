<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
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
