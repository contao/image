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

interface ReadableImageBackendInterface extends ImageBackendInterface
{
    /**
     * @return resource
     */
    public function openStream(TargetDescription $description);
}
