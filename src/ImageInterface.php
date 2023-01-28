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

use Imagine\Image\ImagineInterface;

interface ImageInterface
{
    /**
     * Returns the imagine instance.
     */
    public function getImagine(): ImagineInterface;

    /**
     * Returns the path.
     */
    public function getPath(): string;

    /**
     * Returns the URL relative to the specified root directory,
     * optionally prefixed with the specified URL prefix.
     */
    public function getUrl(string $rootDir, string $prefix = ''): string;

    /**
     * Returns the dimensions.
     */
    public function getDimensions(): ImageDimensions;

    /**
     * Returns the important part.
     */
    public function getImportantPart(): ImportantPart;

    /**
     * Sets the important part.
     */
    public function setImportantPart(ImportantPart $importantPart = null): self;
}
