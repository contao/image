<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image;

interface PictureConfigurationItemInterface
{
    /**
     * Returns the resize configuration.
     */
    public function getResizeConfig(): ResizeConfigurationInterface;

    /**
     * Sets the resize configuration.
     */
    public function setResizeConfig(ResizeConfigurationInterface $resizeConfig): self;

    /**
     * Returns the sizes.
     */
    public function getSizes(): string;

    /**
     * Sets the sizes.
     */
    public function setSizes(string $sizes): self;

    /**
     * Returns the densities.
     */
    public function getDensities(): string;

    /**
     * Sets the densities.
     */
    public function setDensities(string $densities): self;

    /**
     * Returns the media.
     */
    public function getMedia(): string;

    /**
     * Sets the media.
     */
    public function setMedia(string $media): self;
}
