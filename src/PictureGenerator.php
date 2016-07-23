<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image;

/**
 * Generates Picture objects.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class PictureGenerator implements PictureGeneratorInterface
{
    /**
     * @var ResizerInterface
     */
    private $resizer;

    /**
     * @var ResizeOptionsInterface
     */
    private $resizeOptions;

    /**
     * {@inheritdoc}
     */
    public function __construct(ResizerInterface $resizer)
    {
        $this->resizer = $resizer;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(ImageInterface $image, PictureConfigurationInterface $config, ResizeOptionsInterface $options)
    {
        $this->resizeOptions = clone $options;
        $this->resizeOptions->setTargetPath(null);

        $img = $this->generateSource($image, $config->getSize());

        $sources = [];
        foreach ($config->getSizeItems() as $sizeItem) {
            $sources[] = $this->generateSource($image, $sizeItem);
        }

        return new Picture($img, $sources);
    }

    private function generateSource(ImageInterface $image, PictureConfigurationItemInterface $config)
    {
        $densities = [];

        if ($config->getDensities() && (
            $config->getResizeConfig()->getWidth() ||
            $config->getResizeConfig()->getHeight()
        )) {
            $densities = array_filter(array_map(
                'floatval',
                explode(',', $config->getDensities())
            ));
        }

        array_unshift($densities, 1);
        $densities = array_values(array_unique($densities));

        $attributes = [];
        $srcset = [];

        foreach ($densities as $density) {
            $resizeConfig = clone $config->getResizeConfig();
            $resizeConfig->setWidth($resizeConfig->getWidth() * $density);
            $resizeConfig->setHeight($resizeConfig->getHeight() * $density);

            $resizedImage = $this->resizer->resize(
                $image,
                $resizeConfig,
                $this->resizeOptions
            );

            if (empty($attributes['src'])) {
                $attributes['src'] = $resizedImage;
                if (
                    !$resizedImage->getDimensions()->isRelative() &&
                    !$resizedImage->getDimensions()->isUndefined()
                ) {
                    $attributes['width'] = $resizedImage->getDimensions()->getSize()->getWidth();
                    $attributes['height'] = $resizedImage->getDimensions()->getSize()->getHeight();
                }
            }

            $src = [$resizedImage];

            if (count($densities) > 1) {
                // Use pixel density descriptors if the sizes attribute is empty
                if (!$config->getSizes()) {
                    $src[1] = $density . 'x';
                }
                // Otherwise use width descriptors
                else {
                    $src[1] = $resizedImage->getDimensions()->getSize()->getWidth() . 'w';
                }
            }

            $srcset[] = $src;
        }

        $attributes['srcset'] = $srcset;

        if ($config->getSizes()) {
            $attributes['sizes'] = $config->getSizes();
        }

        if ($config->getMedia()) {
            $attributes['media'] = $config->getMedia();
        }

        return $attributes;
    }
}
