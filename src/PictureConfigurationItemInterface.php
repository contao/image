<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image;

/**
 * Picture configuration item interface.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
interface PictureConfigurationItemInterface
{
    /**
     * Returns the resize configuration.
     *
     * @return ResizeConfigurationInterface
     */
    public function getResizeConfig();

    /**
     * Sets the resize configuration.
     *
     * @param ResizeConfigurationInterface $resizeConfig
     *
     * @return self
     */
    public function setResizeConfig(ResizeConfigurationInterface $resizeConfig);

    /**
     * Returns the sizes.
     *
     * @return string
     */
    public function getSizes();

    /**
     * Sets the sizes.
     *
     * @param string $sizes
     *
     * @return self
     */
    public function setSizes($sizes);

    /**
     * Returns the densities.
     *
     * @return string
     */
    public function getDensities();

    /**
     * Sets the densities.
     *
     * @param string $densities
     *
     * @return self
     */
    public function setDensities($densities);

    /**
     * Returns the media.
     *
     * @return string
     */
    public function getMedia();

    /**
     * Sets the media.
     *
     * @param string $media
     *
     * @return self
     */
    public function setMedia($media);
}
