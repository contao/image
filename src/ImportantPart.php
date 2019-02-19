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

    public function __construct(PointInterface $position, BoxInterface $size)
    {
        $this->position = $position;
        $this->size = $size;
    }

    /**
     * {@inheritdoc}
     */
    public function getPosition(): PointInterface
    {
        return $this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize(): BoxInterface
    {
        return $this->size;
    }
}
