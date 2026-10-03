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

interface ImageResizerInterface
{
    public function resize(ImageSourceInterface $source, ResizeConfiguration $configuration, ProcessingOptions $options): ImageResultInterface;
}
