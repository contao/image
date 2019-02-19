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

class ResizeCoordinates implements ResizeCoordinatesInterface
{
    /**
     * @var BoxInterface
     */
    private $size;

    /**
     * @var PointInterface
     */
    private $cropStart;

    /**
     * @var BoxInterface
     */
    private $cropSize;

    public function __construct(BoxInterface $size, PointInterface $cropStart, BoxInterface $cropSize)
    {
        $this->size = $size;
        $this->cropStart = $cropStart;
        $this->cropSize = $cropSize;
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
    public function getCropStart(): PointInterface
    {
        return $this->cropStart;
    }

    /**
     * {@inheritdoc}
     */
    public function getCropSize(): BoxInterface
    {
        return $this->cropSize;
    }

    /**
     * {@inheritdoc}
     */
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
     * {@inheritdoc}
     */
    public function isEqualTo($coordinates): bool
    {
        if ($coordinates instanceof BoxInterface) {
            $coordinates = new static($coordinates, new Point(0, 0), $coordinates);
        }

        if (!$coordinates instanceof ResizeCoordinatesInterface) {
            throw new \InvalidArgumentException(sprintf(
                '$coordinates must be an instance of ResizeCoordinatesInterface or BoxInterface, "%s" given',
                \get_class($coordinates)
            ));
        }

        /* @var ResizeCoordinatesInterface $coordinates */
        return $this->cropStart->getX() === $coordinates->getCropStart()->getX()
            && $this->cropStart->getY() === $coordinates->getCropStart()->getY()
            && $this->cropSize->getWidth() === $coordinates->getCropSize()->getWidth()
            && $this->cropSize->getHeight() === $coordinates->getCropSize()->getHeight()
            && $this->size->getWidth() === $coordinates->getSize()->getWidth()
            && $this->size->getHeight() === $coordinates->getSize()->getHeight()
        ;
    }
}
