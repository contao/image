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

    public function __clone()
    {
        $this->resizeConfig = null === $this->resizeConfig ? null : clone $this->resizeConfig;
    }

    public function getResizeConfig(): ResizeConfiguration
    {
        if (null === $this->resizeConfig) {
            $this->resizeConfig = new ResizeConfiguration();
        }

        return $this->resizeConfig;
    }

    public function getSizes(): string
    {
        return $this->sizes;
    }

    public function getDensities(): string
    {
        return $this->densities;
    }

    public function getMedia(): string
    {
        return $this->media;
    }

    public function withResizeConfig(ResizeConfiguration $resizeConfig): self
    {
        $copy = clone $this;
        $copy->resizeConfig = $resizeConfig;

        return $copy;
    }

    public function withSizes(string $sizes): self
    {
        $copy = clone $this;
        $copy->sizes = $sizes;

        return $copy;
    }

    public function withDensities(string $densities): self
    {
        $copy = clone $this;
        $copy->densities = $densities;

        return $copy;
    }

    public function withMedia(string $media): self
    {
        $copy = clone $this;
        $copy->media = $media;

        return $copy;
    }
}
