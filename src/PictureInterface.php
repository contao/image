<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image;

/**
 * Picture element data.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
interface PictureInterface
{
    /**
     * Constructor.
     *
     * @param array $img     The image tag attributes
     * @param array $sources The source tags attributes
     */
    public function __construct(array $img, array $sources);

    /**
     * Gets the image tag attributes.
     *
     * @param string|null $rootDir Directory to which the URLs should be relative to
     *
     * @return array
     */
    public function getImg($rootDir = null);

    /**
     * Gets the source tags attributes.
     *
     * @param string|null $rootDir Directory to which the URLs should be relative to
     *
     * @return array
     */
    public function getSources($rootDir = null);
}
