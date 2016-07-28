<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image\Picture;

use Contao\Image\Image\ImageInterface;
use Contao\Image\Resize\ResizeOptionsInterface;
use Contao\Image\Resize\ResizerInterface;

/**
 * Generates a Picture object.
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
    public function generate(
        ImageInterface $image,
        PictureConfigurationInterface $config,
        ResizeOptionsInterface $options
    ) {
        $this->resizeOptions = clone $options;
        $this->resizeOptions->setTargetPath(null);

        $sources = [];

        foreach ($config->getSizeItems() as $sizeItem) {
            $sources[] = $this->generateSource($image, $sizeItem);
        }

        return new Picture($this->generateSource($image, $config->getSize()), $sources);
    }

    /**
     * Generates the source.
     *
     * @param ImageInterface                    $image
     * @param PictureConfigurationItemInterface $config
     *
     * @return array
     */
    private function generateSource(ImageInterface $image, PictureConfigurationItemInterface $config)
    {
        $densities = [1];
        $sizesAttribute = $config->getSizes();

        if ($config->getDensities()
            && ($config->getResizeConfig()->getWidth() || $config->getResizeConfig()->getHeight())
        ) {
            if (!$sizesAttribute && false !== strpos($config->getDensities(), 'w')) {
                $sizesAttribute = '100vw';
            }

            $densities = $this->parseDensities($image, $config);
        }

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
                if (!$sizesAttribute) {
                    $src[1] = $density.'x'; // use pixel density descriptors if the sizes attribute is empty
                } else {
                    $src[1] = $resizedImage->getDimensions()->getSize()->getWidth().'w';
                }
            }

            $srcset[] = $src;
        }

        $attributes['srcset'] = $srcset;

        if ($sizesAttribute) {
            $attributes['sizes'] = $sizesAttribute;
        }

        if ($config->getMedia()) {
            $attributes['media'] = $config->getMedia();
        }

        return $attributes;
    }

    /**
     * Parse the densities string and return an array of scaling factors.
     *
     * @param ImageInterface                    $image
     * @param PictureConfigurationItemInterface $config
     *
     * @return array<integer,float>
     */
    private function parseDensities(ImageInterface $image, PictureConfigurationItemInterface $config)
    {
        $width1x = $config->getResizeConfig()->getWidth();

        if (!$width1x && false !== strpos($config->getDensities(), 'w')) {
            $width1x = $this->resizer
                ->resize($image, $config->getResizeConfig(), $this->resizeOptions)
                ->getDimensions()
                ->getSize()
                ->getWidth()
            ;
        }

        $densities = explode(',', $config->getDensities());

        $densities = array_map(
            function ($density) use ($width1x) {
                $type = substr(trim($density), -1);

                if ($type === 'w') {
                    return intval($density) / $width1x;
                } else {
                    return floatval($density);
                }
            },
            $densities
        );

        // Strip empty densities
        $densities = array_filter($densities);

        // Add 1x to the beginning of the list
        array_unshift($densities, 1);

        // Strip duplicates
        $densities = array_values(array_unique($densities));

        return $densities;
    }
}