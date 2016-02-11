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
 * Resize configuration used by the ResizeCalculator.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class ResizeConfiguration
{
    const MODE_CROP = 'crop';
    const MODE_BOX = 'box';
    const MODE_PROPORTIONAL = 'proportional';

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
     * Returns true if the resize would have no effect.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return 0 === $this->width &&
            0 === $this->height &&
            0 === $this->zoomLevel;
    }

    /**
     * Gets the width.
     *
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Sets the width.
     *
     * @param int $width the width
     *
     * @return self
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
     * Gets the height.
     *
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Sets the height.
     *
     * @param int $height the height
     *
     * @return self
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
     * Gets the mode.
     *
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Sets the mode.
     *
     * @param string $mode the mode
     *
     * @return self
     */
    public function setMode($mode)
    {
        if (!in_array($mode, [
            self::MODE_CROP,
            self::MODE_BOX,
            self::MODE_PROPORTIONAL,
        ], true)) {
            throw new \InvalidArgumentException('Mode must be one of the ' . __CLASS__ . '::MODE_* constants');
        }

        $this->mode = $mode;

        return $this;
    }

    /**
     * Gets the zoom level.
     *
     * @return int
     */
    public function getZoomLevel()
    {
        return $this->zoomLevel;
    }

    /**
     * Sets the zoom level.
     *
     * @param int $zoomLevel the zoom level
     *
     * @return self
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
