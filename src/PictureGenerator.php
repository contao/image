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
     * @var bool
     */
    private $bypassCache;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var array
     */
    private $imagineOptions;

    /**
     * {@inheritdoc}
     */
    public function __construct(ResizerInterface $resizer, $bypassCache, $rootDir)
    {
        $this->resizer = $resizer;
        $this->bypassCache = (bool) $bypassCache;
        $this->rootDir = (string) $rootDir;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(ImageInterface $image, PictureConfigurationInterface $config, array $imagineOptions = [])
    {
        $this->imagineOptions = $imagineOptions;

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
                (new ResizeOptions())
                    ->setImagineOptions($this->imagineOptions)
                    ->setBypassCache($this->bypassCache)
            );

            $src = $resizedImage->getUrl($this->rootDir);

            if (empty($attributes['src'])) {
                $attributes['src'] = htmlspecialchars($src, ENT_QUOTES);
                if (
                    !$resizedImage->getDimensions()->isRelative() &&
                    !$resizedImage->getDimensions()->isUndefined()
                ) {
                    $attributes['width'] = $resizedImage->getDimensions()->getSize()->getWidth();
                    $attributes['height'] = $resizedImage->getDimensions()->getSize()->getHeight();
                }
            }

            if (count($densities) > 1) {
                // Use pixel density descriptors if the sizes attribute is empty
                if (!$config->getSizes()) {
                    $src .= ' ' . $density . 'x';
                }
                // Otherwise use width descriptors
                else {
                    $src .= ' ' . $resizedImage->getDimensions()->getSize()->getWidth() . 'w';
                }
            }

            $srcset[] = $src;
        }

        $attributes['srcset'] = htmlspecialchars(implode(', ', $srcset), ENT_QUOTES);

        if ($config->getSizes()) {
            $attributes['sizes'] = htmlspecialchars($config->getSizes(), ENT_QUOTES);
        }

        if ($config->getMedia()) {
            $attributes['media'] = htmlspecialchars($config->getMedia(), ENT_QUOTES);
        }

        return $attributes;
    }
}
