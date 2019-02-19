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

interface PictureInterface
{
    /**
     * Returns the image tag attributes.
     */
    public function getImg(string $rootDir = null, string $prefix = ''): array;

    /**
     * Returns the source tags attributes.
     */
    public function getSources(string $rootDir = null, string $prefix = ''): array;
}
