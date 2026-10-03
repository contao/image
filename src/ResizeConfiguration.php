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
    public const MODE_CROP = 'crop';

    public const MODE_BOX = 'box';

    /**
     * Proportional sizing remains supported.
     */
    public const MODE_PROPORTIONAL = 'proportional';

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
     */
    public function isEmpty(): bool
    {
        return 0 === $this->width && 0 === $this->height && 0 === $this->zoomLevel;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * @return string One of the ResizeConfiguration::MODE_* constants
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    public function getZoomLevel(): int
    {
        return $this->zoomLevel;
    }

    public function withWidth(int $width): self
    {
        $copy = clone $this;

        if ($width < 0) {
            throw new InvalidArgumentException('Width must not be negative');
        }

        $copy->width = $width;

        return $copy;
    }

    public function withHeight(int $height): self
    {
        $copy = clone $this;

        if ($height < 0) {
            throw new InvalidArgumentException('Height must not be negative');
        }

        $copy->height = $height;

        return $copy;
    }

    public function withMode(string $mode): self
    {
        $copy = clone $this;

        if (!\in_array($mode, [self::MODE_CROP, self::MODE_BOX, self::MODE_PROPORTIONAL], true)) {
            throw new InvalidArgumentException('Mode must be one of the '.self::class.'::MODE_* constants');
        }

        $copy->mode = $mode;

        return $copy;
    }

    public function withZoomLevel(int $zoomLevel): self
    {
        $copy = clone $this;

        if ($zoomLevel < 0 || $zoomLevel > 100) {
            throw new InvalidArgumentException('Zoom level must be between 0 and 100');
        }

        $copy->zoomLevel = $zoomLevel;

        return $copy;
    }
}
