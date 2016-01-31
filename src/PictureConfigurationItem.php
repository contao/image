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
 * Picture resize configuration item used by PictureConfiguration
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class PictureConfigurationItem
{
    /**
     * @var ResizeConfiguration
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
     * Gets the resize configuration.
     *
     * @return ResizeConfiguration
     */
    public function getResizeConfig()
    {
        if (null === $this->resizeConfig) {
            $this->setResizeConfig(new ResizeConfiguration);
        }

        return $this->resizeConfig;
    }

    /**
     * Sets the resize configuration.
     *
     * @param ResizeConfiguration $resizeConfig the resize configuration
     *
     * @return self
     */
    public function setResizeConfig(ResizeConfiguration $resizeConfig)
    {
        $this->resizeConfig = $resizeConfig;

        return $this;
    }

    /**
     * Gets the sizes.
     *
     * @return string
     */
    public function getSizes()
    {
        return $this->sizes;
    }

    /**
     * Sets the sizes.
     *
     * @param string $sizes the sizes
     *
     * @return self
     */
    public function setSizes($sizes)
    {
        $this->sizes = (string) $sizes;

        return $this;
    }

    /**
     * Gets the densities.
     *
     * @return string
     */
    public function getDensities()
    {
        return $this->densities;
    }

    /**
     * Sets the densities.
     *
     * @param string $densities the densities
     *
     * @return self
     */
    public function setDensities($densities)
    {
        $this->densities = (string) $densities;

        return $this;
    }

    /**
     * Gets the media.
     *
     * @return string
     */
    public function getMedia()
    {
        return $this->media;
    }

    /**
     * Sets the media.
     *
     * @param string $media the media
     *
     * @return self
     */
    public function setMedia($media)
    {
        $this->media = (string) $media;

        return $this;
    }
}
