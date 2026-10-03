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

interface ImageDecoderEncoderInterface
{
    /**
     * @return resource Output stream positioned at its beginning and owned by the caller
     */
    public function transform(ImageSourceInterface $source, ImageTransformation $transformation);
}
