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
    private ResizeConfiguration|null $resizeConfig = null;

    private string $sizes = '';

    private string $densities = '';

    private string $media = '';

    public function getResizeConfig(): ResizeConfiguration
    {
        return $this->resizeConfig ??= new ResizeConfiguration();
    }

    public function setResizeConfig(ResizeConfiguration $resizeConfig): static
    {
        $this->resizeConfig = $resizeConfig;

        return $this;
    }

    public function getSizes(): string
    {
        return $this->sizes;
    }

    public function setSizes(string $sizes): static
    {
        $this->sizes = $sizes;

        return $this;
    }

    public function getDensities(): string
    {
        return $this->densities;
    }

    public function setDensities(string $densities): static
    {
        $this->densities = $densities;

        return $this;
    }

    public function getMedia(): string
    {
        return $this->media;
    }

    public function setMedia(string $media): static
    {
        $this->media = $media;

        return $this;
    }
}
