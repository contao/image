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

final class Point
{
    public function __construct(
        private int $x,
        private int $y,
    ) {
    }

    public function getX(): int
    {
        return $this->x;
    }

    public function getY(): int
    {
        return $this->y;
    }

    public function toArray(): array
    {
        return [$this->x, $this->y];
    }

    public static function fromArray(array $data): self
    {
        return new self($data[0], $data[1]);
    }
}
