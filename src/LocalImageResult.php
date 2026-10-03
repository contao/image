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

use Contao\Image\Exception\ImageStorageException;

final class LocalImageResult extends ReadableImageResult implements LocalImageResultInterface
{
    public function getPath(): string
    {
        if (!$this->backend instanceof LocalImageBackendInterface) {
            throw new ImageStorageException('Backend has no local path');
        }

        return $this->backend->getPath($this->record->getTarget()->getDescription());
    }
}
