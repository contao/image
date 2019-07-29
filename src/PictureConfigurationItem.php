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

    public function getResizeConfig(): ResizeConfiguration
    {
        if (null === $this->resizeConfig) {
            $this->setResizeConfig(new ResizeConfiguration());
        }

        return $this->resizeConfig;
    }

    public function setResizeConfig(ResizeConfiguration $resizeConfig): self
    {
        $this->resizeConfig = $resizeConfig;

        return $this;
    }

    public function getSizes(): string
    {
        return $this->sizes;
    }

    public function setSizes(string $sizes): self
    {
        $this->sizes = $sizes;

        return $this;
    }

    public function getDensities(): string
    {
        return $this->densities;
    }

    public function setDensities(string $densities): self
    {
        $this->densities = $densities;

        return $this;
    }

    public function getMedia(): string
    {
        return $this->media;
    }

    public function setMedia(string $media): self
    {
        $this->media = $media;

        return $this;
    }
}
