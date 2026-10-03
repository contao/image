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
use Contao\Image\Exception\InvalidArgumentException;

final class BackendRegistry
{
    private array $backends = [];

    public function __construct(iterable $backends)
    {
        foreach ($backends as $backend) {
            if (isset($this->backends[$backend->getIdentifier()])) {
                throw new InvalidArgumentException('Duplicate backend identifier');
            }
            $this->backends[$backend->getIdentifier()] = $backend;
        }
    }

    public function get(string $identifier): ImageBackendInterface
    {
        return $this->backends[$identifier] ?? throw new ImageStorageException('Backend is unavailable: '.$identifier);
    }
}
