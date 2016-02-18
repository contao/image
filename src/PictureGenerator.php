<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Image;

/**
 * Generates Picture objects.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class PictureGenerator
{
    /**
     * @var Resizer
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
     * Constructor.
     *
     * @param Resizer $resizer     The resizer object
     * @param bool    $bypassCache True to bypass the image cache
     * @param string  $rootDir     Path to root directory
     */
    public function __construct(Resizer $resizer, $bypassCache, $rootDir)
    {
        $this->resizer = $resizer;
        $this->bypassCache = (bool) $bypassCache;
        $this->rootDir = (string) $rootDir;
    }

    /**
     * Generates a Picture object.
     *
     * @param Image                $image  The Image object
     * @param PictureConfiguration $config The configuration
     *
     * @return Picture The generated Picture object
     */
    public function generate(Image $image, PictureConfiguration $config)
    {
        $img = $this->generateSource($image, $config->getSize());

        $sources = [];
        foreach ($config->getSizeItems() as $sizeItem) {
            $sources[] = $this->generateSource($image, $sizeItem);
        }

        return new Picture($img, $sources);
    }

    private function generateSource(Image $image, PictureConfigurationItem $config)
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

            if (!$image->getDimensions()->isUndefined() && !$resizeConfig->isEmpty()) {
                $resizedImage = $this->resizer->resize($image, $resizeConfig, null, $this->bypassCache);
            }
            else {
                $resizedImage = $image;
            }

            $src = $this->pathToUrl($resizedImage->getPath());

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

    private function pathToUrl($path)
    {
        if (
            substr($path, 0, strlen($this->rootDir) + 1) === $this->rootDir . '/'
            || substr($path, 0, strlen($this->rootDir) + 1) === $this->rootDir . '\\'
        ) {
            $url = substr($path, strlen($this->rootDir) + 1);
        }
        else {
            throw new \InvalidArgumentException('Path "' . $path . '" is not inside root directory "' . $this->rootDir . '"');
        }

        // TODO: add TL_FILES_URL to src
        $url = str_replace('%2F', '/', rawurlencode($url));

        return $url;
    }
}
