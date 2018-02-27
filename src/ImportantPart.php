<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image;

use Imagine\Image\BoxInterface;
use Imagine\Image\PointInterface;

class ImportantPart implements ImportantPartInterface
{
    /**
     * @var PointInterface
     */
    private $position;

    /**
     * @var BoxInterface
     */
    private $size;

    /**
     * @param PointInterface $position
     * @param BoxInterface   $size
     */
    public function __construct(PointInterface $position, BoxInterface $size)
    {
        $this->position = $position;
        $this->size = $size;
    }

    /**
     * {@inheritdoc}
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize()
    {
        return $this->size;
    }
}
