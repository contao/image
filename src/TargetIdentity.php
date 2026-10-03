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

use Contao\Image\Exception\InvalidArgumentException;

final class TargetIdentity
{
    public function __construct(private string $path)
    {
        if (!preg_match('~^[a-f0-9]{64}/[a-f0-9]{64}/[a-f0-9]{64}/[a-zA-Z0-9_.-]+$~D', $path)) {
            throw new InvalidArgumentException('Invalid target identity');
        }
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getSourceHash(): string
    {
        return explode('/', $this->path)[0];
    }

    public function getVersionHash(): string
    {
        return explode('/', $this->path)[1];
    }

    public function getFilename(): string
    {
        return basename($this->path);
    }
}
