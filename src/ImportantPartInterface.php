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
 * Important part interface.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
interface ImportantPartInterface
{
    /**
     * Constructor.
     *
     * @param PointInterface $position
     * @param BoxInterface   $size
     */
    public function __construct(PointInterface $position, BoxInterface $size);

    /**
     * Returns the position.
     *
     * @return PointInterface
     */
    public function getPosition();

    /**
     * Returns the size.
     *
     * @return BoxInterface
     */
    public function getSize();
}
