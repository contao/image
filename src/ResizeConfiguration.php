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

use Contao\Image\Exception\InvalidArgumentException;

class ResizeConfiguration
{
    final public const MODE_CROP = 'crop';
    final public const MODE_BOX = 'box';
    final public const MODE_PROPORTIONAL = 'proportional';

    private int $width = 0;

    private int $height = 0;

    private string $mode = self::MODE_CROP;

    private int $zoomLevel = 0;

    /**
     * Returns true if the resize would have no effect.
     */
    public function isEmpty(): bool
    {
        return 0 === $this->width && 0 === $this->height && 0 === $this->zoomLevel;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function setWidth(int $width): self
    {
        if ($width < 0) {
            throw new InvalidArgumentException('Width must not be negative');
        }

        $this->width = $width;

        return $this;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function setHeight(int $height): self
    {
        if ($height < 0) {
            throw new InvalidArgumentException('Height must not be negative');
        }

        $this->height = $height;

        return $this;
    }

    /**
     * @return string One of the ResizeConfiguration::MODE_* constants
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * @param string $mode One of the ResizeConfiguration::MODE_* constants
     */
    public function setMode(string $mode): self
    {
        if (!\in_array($mode, [self::MODE_CROP, self::MODE_BOX, self::MODE_PROPORTIONAL], true)) {
            throw new InvalidArgumentException('Mode must be one of the '.self::class.'::MODE_* constants');
        }

        $this->mode = $mode;

        return $this;
    }

    public function getZoomLevel(): int
    {
        return $this->zoomLevel;
    }

    public function setZoomLevel(int $zoomLevel): self
    {
        if ($zoomLevel < 0 || $zoomLevel > 100) {
            throw new InvalidArgumentException('Zoom level must be between 0 and 100');
        }

        $this->zoomLevel = $zoomLevel;

        return $this;
    }
}
