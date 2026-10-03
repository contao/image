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

interface ResponsivePictureGeneratorInterface
{
    public function generate(ImageSourceInterface $source, PictureConfiguration $configuration, ProcessingOptions $options): ResponsivePictureInterface;
}
