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

use Imagine\Image\BoxInterface;
use Imagine\Image\Point;
use Imagine\Image\PointInterface;

class ResizeCoordinates
{
    public function __construct(
        private readonly BoxInterface $size,
        private readonly PointInterface $cropStart,
        private readonly BoxInterface $cropSize,
    ) {
    }

    public function getSize(): BoxInterface
    {
        return $this->size;
    }

    public function getCropStart(): PointInterface
    {
        return $this->cropStart;
    }

    public function getCropSize(): BoxInterface
    {
        return $this->cropSize;
    }

    public function getHash(): string
    {
        return md5(implode(',', [
            (int) $this->size->getWidth(),
            (int) $this->size->getHeight(),
            (int) $this->cropStart->getX(),
            (int) $this->cropStart->getY(),
            (int) $this->cropSize->getWidth(),
            (int) $this->cropSize->getHeight(),
        ]));
    }

    /**
     * Compares the coordinates with another ResizeCoordinates or Box object.
     */
    public function isEqualTo(BoxInterface|self $coordinates): bool
    {
        if ($coordinates instanceof BoxInterface) {
            $coordinates = new self($coordinates, new Point(0, 0), $coordinates);
        }

        return $this->cropStart->getX() === $coordinates->getCropStart()->getX()
            && $this->cropStart->getY() === $coordinates->getCropStart()->getY()
            && $this->cropSize->getWidth() === $coordinates->getCropSize()->getWidth()
            && $this->cropSize->getHeight() === $coordinates->getCropSize()->getHeight()
            && $this->size->getWidth() === $coordinates->getSize()->getWidth()
            && $this->size->getHeight() === $coordinates->getSize()->getHeight();
    }
}
