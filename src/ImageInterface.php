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
    public function getImagine(): ImagineInterface;

    public function getPath(): string;

    /**
     * Returns the URL relative to the specified root directory,
     * optionally prefixed with the specified URL prefix.
     */
    public function getUrl(string $rootDir, string $prefix = ''): string;

    public function getDimensions(): ImageDimensions;

    public function getImportantPart(): ImportantPart;

    public function setImportantPart(ImportantPart $importantPart = null): static;
}
