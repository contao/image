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

    /**
     * @var 0|positive-int
     */
    private int $width = 0;

    /**
     * @var 0|positive-int
     */
    private int $height = 0;

    /**
     * @var self::MODE_*
     */
    private string $mode = self::MODE_CROP;

    private int $zoomLevel = 0;

    /**
     * Returns true if the resize would have no effect.
     */
    public function isEmpty(): bool
    {
        return 0 === $this->width && 0 === $this->height && 0 === $this->zoomLevel;
    }

    /**
     * @return 0|positive-int
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * @param 0|positive-int $width
     */
    public function setWidth(int $width): static
    {
        if ($width < 0) {
            throw new InvalidArgumentException('Width must not be negative');
        }

        $this->width = $width;

        return $this;
    }

    /**
     * @return 0|positive-int
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * @param 0|positive-int $height
     */
    public function setHeight(int $height): static
    {
        if ($height < 0) {
            throw new InvalidArgumentException('Height must not be negative');
        }

        $this->height = $height;

        return $this;
    }

    /**
     * @return self::MODE_*
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * @param self::MODE_* $mode
     */
    public function setMode(string $mode): static
    {
        if ('proportional' === $mode) {
            trigger_deprecation('contao/image', '1.2', 'Using mode "proportional" has been deprecated and will no longer work in version 3.0. Use ResizeConfiguration::MODE_BOX instead.');

            $mode = self::MODE_BOX;
        }

        if (!\in_array($mode, [self::MODE_CROP, self::MODE_BOX], true)) {
            throw new InvalidArgumentException('Mode must be one of the '.self::class.'::MODE_* constants');
        }

        $this->mode = $mode;

        return $this;
    }

    /**
     * @return int<0,100>
     */
    public function getZoomLevel(): int
    {
        return $this->zoomLevel;
    }

    /**
     * @param int<0,100> $zoomLevel
     */
    public function setZoomLevel(int $zoomLevel): static
    {
        if ($zoomLevel < 0 || $zoomLevel > 100) {
            throw new InvalidArgumentException('Zoom level must be between 0 and 100');
        }

        $this->zoomLevel = $zoomLevel;

        return $this;
    }
}
