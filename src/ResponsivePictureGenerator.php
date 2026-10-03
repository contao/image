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

use Contao\Image\Internal\PictureGeneration;

final class ResponsivePictureGenerator implements ResponsivePictureGeneratorInterface
{
    public function __construct(private ImageResizerInterface $resizer, private SourceInformationProvider $information, private ImageLifecycle $lifecycle)
    {
    }

    public function generate(ImageSourceInterface $source, PictureConfiguration $configuration, ProcessingOptions $options): ResponsivePictureInterface
    {
        $version = SourceVersion::capture($source);
        $lock = $this->lifecycle->acquire($version);

        try {
            $information = $this->information->get($source, $version);

            return (new PictureGeneration($this->resizer, $information, $source))->generate(clone $configuration, $options);
        } finally {
            $lock->release();
        }
    }
}
