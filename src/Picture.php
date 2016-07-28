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
 * Picture element data.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class Picture implements PictureInterface
{
    /**
     * Image tag attributes.
     *
     * @var array
     */
    private $img = [];

    /**
     * Source tags attributes.
     *
     * @var array
     */
    private $sources = [];

    /**
     * {@inheritdoc}
     */
    public function __construct(array $img, array $sources)
    {
        if (!isset($img['src'])) {
            throw new \InvalidArgumentException('Missing src attribute in img');
        }
        if (!isset($img['srcset'])) {
            throw new \InvalidArgumentException('Missing src attribute in img');
        }
        if (!($img['src'] instanceof ImageInterface)) {
            throw new \InvalidArgumentException('Src must be of type ImageInterface');
        }
        foreach ($img['srcset'] as $src) {
            if (!($src[0] instanceof ImageInterface)) {
                throw new \InvalidArgumentException('Srcets must be of type ImageInterface');
            }
        }

        foreach ($sources as $source) {
            if (!isset($source['srcset'])) {
                throw new \InvalidArgumentException('Missing srcset attribute in source');
            }
            foreach ($img['srcset'] as $src) {
                if (!($src[0] instanceof ImageInterface)) {
                    throw new \InvalidArgumentException('Srcets must be of type ImageInterface');
                }
            }
        }

        $this->img = $img;
        $this->sources = $sources;
    }

    /**
     * {@inheritdoc}
     */
    public function getImg($rootDir = null)
    {
        if ($rootDir === null) {
            return $this->img;
        }

        return $this->buildUrls($this->img, $rootDir);
    }

    /**
     * {@inheritdoc}
     */
    public function getSources($rootDir = null)
    {
        if ($rootDir === null) {
            return $this->sources;
        }

        return array_map(function ($source) use ($rootDir) {
            return $this->buildUrls($source, $rootDir);
        }, $this->sources);
    }

    /**
     * Converts image objects in an attributes array to URLs.
     *
     * @param array  $img
     * @param string $rootDir
     *
     * @return array
     */
    private function buildUrls($img, $rootDir)
    {
        if (isset($img['src'])) {
            $img['src'] = $img['src']->getUrl($rootDir);
        }

        $img['srcset'] = array_map(function ($src) use ($rootDir) {
            $src[0] = $src[0]->getUrl($rootDir);

            return implode(' ', $src);
        }, $img['srcset']);
        $img['srcset'] = implode(', ', $img['srcset']);

        return $img;
    }
}
