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

final class LocalStoredImageBackend extends ReadableStoredImageBackend implements LocalImageBackendInterface
{
    public function __construct(ImageDecoderEncoderInterface $codec, private LocalTargetContentStorageInterface $localStorage, string $namespace = 'imagine-v1')
    {
        parent::__construct($codec, $localStorage, $namespace);
    }

    public function getPath(TargetDescription $description): string
    {
        return $this->localStorage->getPath($description->getKey());
    }
}
