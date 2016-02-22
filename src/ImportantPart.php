<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image;

use Imagine\Image\BoxInterface;
use Imagine\Image\PointInterface;

/**
 * Important part used by the ResizeCalculator.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class ImportantPart
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
     * Constructor.
     *
     * @param PointInterface $position The position
     * @param BoxInterface   $size     The size
     */
    public function __construct(
        PointInterface $position,
        BoxInterface $size
    ) {
        $this->position = $position;
        $this->size = $size;
    }

    /**
     * Gets the position.
     *
     * @return PointInterface
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Gets the size.
     *
     * @return BoxInterface
     */
    public function getSize()
    {
        return $this->size;
    }
}
