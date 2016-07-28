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
 * Picture resize configuration item used by PictureConfiguration.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
interface PictureConfigurationItemInterface
{
    /**
     * Gets the resize configuration.
     *
     * @return ResizeConfigurationInterface
     */
    public function getResizeConfig();

    /**
     * Sets the resize configuration.
     *
     * @param ResizeConfigurationInterface $resizeConfig the resize configuration
     *
     * @return self
     */
    public function setResizeConfig(ResizeConfigurationInterface $resizeConfig);

    /**
     * Gets the sizes.
     *
     * @return string
     */
    public function getSizes();

    /**
     * Sets the sizes.
     *
     * @param string $sizes the sizes
     *
     * @return self
     */
    public function setSizes($sizes);

    /**
     * Gets the densities.
     *
     * @return string
     */
    public function getDensities();

    /**
     * Sets the densities.
     *
     * @param string $densities the densities
     *
     * @return self
     */
    public function setDensities($densities);

    /**
     * Gets the media.
     *
     * @return string
     */
    public function getMedia();

    /**
     * Sets the media.
     *
     * @param string $media the media
     *
     * @return self
     */
    public function setMedia($media);
}
