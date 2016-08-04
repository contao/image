<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image\Event;

/**
 * Defines constants for the Contao image events.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
final class ContaoImageEvents
{
    /**
     * The contao.image.resize_image event is triggered when an image is resized.
     *
     * @var string
     *
     * @see Contao\Image\Event\ResizeImageEvent
     */
    const RESIZE_IMAGE = 'contao.image.resize_image';
}
