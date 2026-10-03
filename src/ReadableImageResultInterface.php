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

interface ReadableImageResultInterface extends ImageResultInterface
{
    /**
     * @return resource A fresh readable stream owned by the caller
     */
    public function openStream();

    public function asSource(): ImageSourceInterface;
}
