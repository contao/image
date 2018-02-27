<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image;

class ResizeConfiguration implements ResizeConfigurationInterface
{
    /**
     * @var int
     */
    private $width = 0;

    /**
     * @var int
     */
    private $height = 0;

    /**
     * @var string
     */
    private $mode = self::MODE_CROP;

    /**
     * @var int
     */
    private $zoomLevel = 0;

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        return 0 === $this->width && 0 === $this->height && 0 === $this->zoomLevel;
    }

    /**
     * {@inheritdoc}
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * {@inheritdoc}
     */
    public function setWidth($width)
    {
        $width = (int) $width;

        if ($width < 0) {
            throw new \InvalidArgumentException('Width must not be negative');
        }

        $this->width = $width;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * {@inheritdoc}
     */
    public function setHeight($height)
    {
        $height = (int) $height;

        if ($height < 0) {
            throw new \InvalidArgumentException('Height must not be negative');
        }

        $this->height = $height;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * {@inheritdoc}
     */
    public function setMode($mode)
    {
        if (!\in_array($mode, [self::MODE_CROP, self::MODE_BOX, self::MODE_PROPORTIONAL], true)) {
            throw new \InvalidArgumentException('Mode must be one of the '.__CLASS__.'::MODE_* constants');
        }

        $this->mode = $mode;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getZoomLevel()
    {
        return $this->zoomLevel;
    }

    /**
     * {@inheritdoc}
     */
    public function setZoomLevel($zoomLevel)
    {
        $zoomLevel = (int) $zoomLevel;

        if ($zoomLevel < 0 || $zoomLevel > 100) {
            throw new \InvalidArgumentException('Zoom level must be between 0 and 100');
        }

        $this->zoomLevel = $zoomLevel;

        return $this;
    }
}
