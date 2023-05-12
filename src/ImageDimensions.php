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
use Contao\ImagineSvg\SvgBox;
use Imagine\Image\BoxInterface;

class ImageDimensions
{
    /**
     * Exif 2.32 orientation attribute (tag 274).
     *
     * @see <http://www.cipa.jp/std/documents/e/DC-008-Translation-2019-E.pdf>
     */
    final public const ORIENTATION_NORMAL = 1;
    final public const ORIENTATION_90 = 6;
    final public const ORIENTATION_180 = 3;
    final public const ORIENTATION_270 = 8;
    final public const ORIENTATION_MIRROR = 2;
    final public const ORIENTATION_MIRROR_90 = 7;
    final public const ORIENTATION_MIRROR_180 = 4;
    final public const ORIENTATION_MIRROR_270 = 5;

    private readonly bool $relative;

    private readonly bool $undefined;

    public function __construct(
        private readonly BoxInterface $size,
        bool $relative = null,
        bool $undefined = null,
        private readonly int $orientation = self::ORIENTATION_NORMAL,
    ) {
        if ($orientation < 1 || $orientation > 8) {
            throw new InvalidArgumentException('Orientation must be one of the ImageDimensions::ORIENTATION_* constants');
        }

        $this->relative = $relative ?? $size instanceof SvgBox && SvgBox::TYPE_ASPECT_RATIO === $size->getType();
        $this->undefined = $undefined ?? $size instanceof SvgBox && SvgBox::TYPE_NONE === $size->getType();
    }

    public function getSize(): BoxInterface
    {
        return $this->size;
    }

    public function getOrientation(): int
    {
        return $this->orientation;
    }

    public function isRelative(): bool
    {
        return $this->relative;
    }

    public function isUndefined(): bool
    {
        return $this->undefined;
    }
}
