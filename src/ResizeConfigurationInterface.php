<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image;

interface ResizeConfigurationInterface
{
    const MODE_CROP = 'crop';
    const MODE_BOX = 'box';
    const MODE_PROPORTIONAL = 'proportional';

    /**
     * Returns true if the resize would have no effect.
     *
     * @return bool
     */
    public function isEmpty();

    /**
     * Returns the width.
     *
     * @return int
     */
    public function getWidth();

    /**
     * Sets the width.
     *
     * @param int $width the width
     *
     * @return self
     */
    public function setWidth($width);

    /**
     * Returns the height.
     *
     * @return int
     */
    public function getHeight();

    /**
     * Sets the height.
     *
     * @param int $height the height
     *
     * @return self
     */
    public function setHeight($height);

    /**
     * Returns the mode.
     *
     * @return string
     */
    public function getMode();

    /**
     * Sets the mode.
     *
     * @param string $mode the mode
     *
     * @return self
     */
    public function setMode($mode);

    /**
     * Returns the zoom level.
     *
     * @return int
     */
    public function getZoomLevel();

    /**
     * Sets the zoom level.
     *
     * @param int $zoomLevel the zoom level
     *
     * @return self
     */
    public function setZoomLevel($zoomLevel);
}
