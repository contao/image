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
 * Important part class.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
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
     * {@inheritdoc}
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
