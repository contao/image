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

use Contao\ImagineSvg\RelativeBoxInterface;
use Contao\ImagineSvg\UndefinedBoxInterface;
use Imagine\Image\BoxInterface;

class ImageDimensions implements ImageDimensionsInterface
{
    /**
     * @var BoxInterface
     */
    private $size;

    /**
     * @var bool
     */
    private $relative;

    /**
     * @var bool
     */
    private $undefined;

    public function __construct(BoxInterface $size, bool $relative = null, bool $undefined = null)
    {
        if (null === $relative && $size instanceof RelativeBoxInterface) {
            $relative = true;
        }

        if (null === $undefined && $size instanceof UndefinedBoxInterface) {
            $undefined = true;
        }

        $this->size = $size;
        $this->relative = (bool) $relative;
        $this->undefined = (bool) $undefined;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize(): BoxInterface
    {
        return $this->size;
    }

    /**
     * {@inheritdoc}
     */
    public function isRelative(): bool
    {
        return $this->relative;
    }

    /**
     * {@inheritdoc}
     */
    public function isUndefined(): bool
    {
        return $this->undefined;
    }
}
