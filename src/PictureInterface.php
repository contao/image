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
     * @param string $rootDir Directory to which the URLs should be relative to
     *
     * @return array
     */
    public function getImg($rootDir);

    /**
     * Gets the source tags attributes.

     * @param string $rootDir Directory to which the URLs should be relative to
     *
     * @return array
     */
    public function getSources($rootDir);
}
