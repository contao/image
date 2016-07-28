<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
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
interface ImportantPartInterface
{
    /**
     * Constructor.
     *
     * @param PointInterface $position The position
     * @param BoxInterface   $size     The size
     */
    public function __construct(
        PointInterface $position,
        BoxInterface $size
    );

    /**
     * Gets the position.
     *
     * @return PointInterface
     */
    public function getPosition();

    /**
     * Gets the size.
     *
     * @return BoxInterface
     */
    public function getSize();
}
