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

use Contao\Image\Exception\SourceUnavailableException;

final class ExportedImageResult implements LocalImageResultInterface
{
    public function __construct(private ImageResultInterface $original, private string $path)
    {
    }

    public function getIdentity(): TargetIdentity
    {
        return $this->original->getIdentity();
    }

    public function getDimensions(): Dimensions
    {
        return $this->original->getDimensions();
    }

    public function getFormat(): string
    {
        return $this->original->getFormat();
    }

    public function getUrl(): string
    {
        return $this->original->getUrl();
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function openStream()
    {
        $stream = @fopen($this->path, 'r');

        if (false === $stream) {
            throw new SourceUnavailableException('Exported image is unavailable');
        }

        return $stream;
    }

    public function asSource(): ImageSourceInterface
    {
        if (!$this->original instanceof ReadableImageResultInterface) {
            throw new SourceUnavailableException('Result is not readable');
        }

        return $this->original->asSource();
    }
}
