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
    public function getResizeConfig(): ResizeConfigurationInterface
    {
        if (null === $this->resizeConfig) {
            $this->setResizeConfig(new ResizeConfiguration());
        }

        return $this->resizeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function setResizeConfig(ResizeConfigurationInterface $resizeConfig): PictureConfigurationItemInterface
    {
        $this->resizeConfig = $resizeConfig;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSizes(): string
    {
        return $this->sizes;
    }

    /**
     * {@inheritdoc}
     */
    public function setSizes(string $sizes): PictureConfigurationItemInterface
    {
        $this->sizes = $sizes;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDensities(): string
    {
        return $this->densities;
    }

    /**
     * {@inheritdoc}
     */
    public function setDensities(string $densities): PictureConfigurationItemInterface
    {
        $this->densities = $densities;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getMedia(): string
    {
        return $this->media;
    }

    /**
     * {@inheritdoc}
     */
    public function setMedia(string $media): PictureConfigurationItemInterface
    {
        $this->media = $media;

        return $this;
    }
}
