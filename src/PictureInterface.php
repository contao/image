<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image;

/**
 * @method array getRawImg()
 * @method array getRawSources()
 */
interface PictureInterface
{
    /**
     * Returns the image tag attributes.
     *
     * @param string $rootDir passing NULL is deprecated
     */
    public function getImg(string $rootDir = null, string $prefix = ''): array;

    /**
     * Returns the source tags attributes.
     *
     * @param string $rootDir passing NULL is deprecated
     */
    public function getSources(string $rootDir = null, string $prefix = ''): array;
}
