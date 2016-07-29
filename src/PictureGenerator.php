<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image;

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

        $descriptorType = '';

        if (count($densities) > 1) {
            $descriptorType = $sizesAttribute ? 'w' : 'x'; // use pixel density descriptors if the sizes attribute is empty
        }

        foreach ($densities as $density) {
            $srcset[] = $this->generateSrcsetItem($image, $config, $density, $descriptorType);
        }

        $attributes['srcset'] = $srcset;

        $attributes['src'] = $srcset[0][0];

        if (
            !$attributes['src']->getDimensions()->isRelative() &&
            !$attributes['src']->getDimensions()->isUndefined()
        ) {
            $attributes['width'] = $attributes['src']->getDimensions()->getSize()->getWidth();
            $attributes['height'] = $attributes['src']->getDimensions()->getSize()->getHeight();
        }

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

    /**
     * Generates a srcset item.
     *
     * @param ImageInterface                    $image
     * @param PictureConfigurationItemInterface $config
     * @param float                             $density
     * @param string                            $descriptorType x, w or the empty string
     *
     * @return array Array containing an ImageInterface and an optional descriptor string
     */
    private function generateSrcsetItem(
        ImageInterface $image,
        PictureConfigurationItemInterface $config,
        $density,
        $descriptorType
    ) {
        $resizeConfig = clone $config->getResizeConfig();
        $resizeConfig->setWidth($resizeConfig->getWidth() * $density);
        $resizeConfig->setHeight($resizeConfig->getHeight() * $density);

        $resizedImage = $this->resizer->resize($image, $resizeConfig, $this->resizeOptions);

        $src = [$resizedImage];

        if ('x' === $descriptorType) {
            $src[1] = $density.'x';
        } elseif ('w' === $descriptorType) {
            $src[1] = $resizedImage->getDimensions()->getSize()->getWidth().'w';
        }

        return $src;
    }
}
