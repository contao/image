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

final class Size
{
    public function __construct(private int $width, private int $height)
    {
        if ($width < 1 || $height < 1) {
            throw new InvalidArgumentException('Image dimensions must be positive');
        }
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function toArray(): array
    {
        return [$this->width, $this->height];
    }

    public static function fromArray(array $data): self
    {
        return new self($data[0], $data[1]);
    }
}
