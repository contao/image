<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image;

class PictureConfigurationItem implements PictureConfigurationItemInterface
{
    /**
     * @var ResizeConfigurationInterface
     */
    private $resizeConfig;

    /**
     * @var string
     */
    private $sizes = '';

    /**
     * @var string
     */
    private $densities = '';

    /**
     * @var string
     */
    private $media = '';

    /**
     * {@inheritdoc}
     */
    public function getResizeConfig()
    {
        if (null === $this->resizeConfig) {
            $this->setResizeConfig(new ResizeConfiguration());
        }

        return $this->resizeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function setResizeConfig(ResizeConfigurationInterface $resizeConfig)
    {
        $this->resizeConfig = $resizeConfig;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSizes()
    {
        return $this->sizes;
    }

    /**
     * {@inheritdoc}
     */
    public function setSizes($sizes)
    {
        $this->sizes = (string) $sizes;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDensities()
    {
        return $this->densities;
    }

    /**
     * {@inheritdoc}
     */
    public function setDensities($densities)
    {
        $this->densities = (string) $densities;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getMedia()
    {
        return $this->media;
    }

    /**
     * {@inheritdoc}
     */
    public function setMedia($media)
    {
        $this->media = (string) $media;

        return $this;
    }
}
