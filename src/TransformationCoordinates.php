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

final class TransformationCoordinates
{
    public function __construct(private Size $size, private Point $cropStart, private Size $cropSize)
    {
    }

    public function getSize(): Size
    {
        return $this->size;
    }

    public function getCropStart(): Point
    {
        return $this->cropStart;
    }

    public function getCropSize(): Size
    {
        return $this->cropSize;
    }

    public function isEqualTo(Size $size): bool
    {
        return $this->size->toArray() === $size->toArray() && $this->cropSize->toArray() === $size->toArray() && [0, 0] === $this->cropStart->toArray();
    }

    public function toArray(): array
    {
        return ['size' => $this->size->toArray(), 'start' => $this->cropStart->toArray(), 'crop' => $this->cropSize->toArray()];
    }

    public static function fromArray(array $data): self
    {
        return new self(Size::fromArray($data['size']), Point::fromArray($data['start']), Size::fromArray($data['crop']));
    }
}
