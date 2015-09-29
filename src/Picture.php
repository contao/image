<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Image;

/**
 * Picture element data
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class Picture
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
     * Constructor.
     *
     * @param array $img     The image tag attributes
     * @param array $sources The source tags attributes
     */
    public function __construct(array $img, array $sources) {
        $this->img = $img;
        $this->sources = $sources;
    }

    /**
     * Gets the image tag attributes.
     *
     * @return array
     */
    public function getImg()
    {
        return $this->img;
    }

    /**
     * Gets the source tags attributes.
     *
     * @return array
     */
    public function getSources()
    {
        return $this->sources;
    }
}
