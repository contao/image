<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image\Event;

use Contao\Image\ImageInterface;
use Contao\Image\ResizeConfigurationInterface;
use Contao\Image\ResizeCoordinatesInterface;
use Contao\Image\ResizeOptionsInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Allows to set a resized image.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ResizeImageEvent extends Event
{
    /**
     * @var ImageInterface
     */
    private $image;

    /**
     * @var ResizeCoordinatesInterface
     */
    private $coordinates;

    /**
     * @var string
     */
    private $path;

    /**
     * @var ResizeConfigurationInterface
     */
    private $config;

    /**
     * @var ResizeOptionsInterface
     */
    private $options;

    /**
     * @var ImageInterface
     */
    private $resizedImage;

    /**
     * Constructor.
     *
     * @param ImageInterface               $image
     * @param ResizeCoordinatesInterface   $coordinates
     * @param string                       $path
     * @param ResizeConfigurationInterface $config
     * @param ResizeOptionsInterface       $options
     */
    public function __construct(
        ImageInterface $image,
        ResizeCoordinatesInterface $coordinates,
        $path,
        ResizeConfigurationInterface $config,
        ResizeOptionsInterface $options
    ) {
        $this->image = $image;
        $this->coordinates = $coordinates;
        $this->path = $path;
        $this->config = $config;
        $this->options = $options;
    }

    /**
     * Returns the image object.
     *
     * @return ImageInterface
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Returns the coordinates object.
     *
     * @return ResizeCoordinatesInterface
     */
    public function getCoordinates()
    {
        return $this->coordinates;
    }

    /**
     * Returns the path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Returns the options.
     *
     * @return ResizeOptionsInterface
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Returns the configuration.
     *
     * @return ResizeConfigurationInterface
     */
    public function getConfiguration()
    {
        return $this->config;
    }

    /**
     * Returns the resized image.
     *
     * @return ImageInterface
     */
    public function getResizedImage()
    {
        return $this->resizedImage;
    }

    /**
     * Sets the resized image.
     *
     * @param ImageInterface|null $image
     */
    public function setResizedImage(ImageInterface $image = null)
    {
        $this->resizedImage = $image;
    }
}
