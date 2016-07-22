<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image;

/**
 * Picture element data.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class Picture implements PictureInterface
{
    /**
     * Image tag attributes.
     *
     * @var array
     */
    private $img = [];

    /**
     * Source tags attributes.
     *
     * @var array
     */
    private $sources = [];

    /**
     * {@inheritdoc}
     */
    public function __construct(array $img, array $sources)
    {
        $this->img = $img;
        $this->sources = $sources;
    }

    /**
     * {@inheritdoc}
     */
    public function getImg()
    {
        return $this->img;
    }

    /**
     * {@inheritdoc}
     */
    public function getSources()
    {
        return $this->sources;
    }
}
