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

interface ResizeConfigurationInterface
{
    public const MODE_CROP = 'crop';
    public const MODE_BOX = 'box';
    public const MODE_PROPORTIONAL = 'proportional';

    /**
     * Returns true if the resize would have no effect.
     */
    public function isEmpty(): bool;

    /**
     * Returns the width.
     */
    public function getWidth(): int;

    /**
     * Sets the width.
     *
     * @param int $width the width
     */
    public function setWidth(int $width): self;

    /**
     * Returns the height.
     */
    public function getHeight(): int;

    /**
     * Sets the height.
     *
     * @param int $height the height
     */
    public function setHeight(int $height): self;

    /**
     * Returns the mode.
     */
    public function getMode(): string;

    /**
     * Sets the mode.
     *
     * @param string $mode the mode
     */
    public function setMode(string $mode): self;

    /**
     * Returns the zoom level.
     */
    public function getZoomLevel(): int;

    /**
     * Sets the zoom level.
     *
     * @param int $zoomLevel the zoom level
     */
    public function setZoomLevel(int $zoomLevel): self;
}
