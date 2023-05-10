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
use Contao\Image\Metadata\ExifFormat;
use Contao\Image\Metadata\GifFormat;
use Contao\Image\Metadata\IptcFormat;
use Contao\Image\Metadata\PngFormat;
use Contao\Image\Metadata\XmpFormat;
use Symfony\Component\Filesystem\Filesystem;

class ResizeOptions
{
    private array $imagineOptions = [];

    private string|null $targetPath = null;

    private bool $bypassCache = false;

    private bool $skipIfDimensionsMatch = false;

    private array $preserveCopyrightMetadata = [
        XmpFormat::NAME => XmpFormat::DEFAULT_PRESERVE_KEYS,
        IptcFormat::NAME => IptcFormat::DEFAULT_PRESERVE_KEYS,
        ExifFormat::NAME => ExifFormat::DEFAULT_PRESERVE_KEYS,
        PngFormat::NAME => PngFormat::DEFAULT_PRESERVE_KEYS,
        GifFormat::NAME => GifFormat::DEFAULT_PRESERVE_KEYS,
    ];

    public function getImagineOptions(): array
    {
        return $this->imagineOptions;
    }

    public function setImagineOptions(array $imagineOptions): self
    {
        $this->imagineOptions = $imagineOptions;

        return $this;
    }

    public function getTargetPath(): string|null
    {
        return $this->targetPath;
    }

    public function setTargetPath(string|null $targetPath): self
    {
        if (null !== $targetPath && !(new Filesystem())->isAbsolutePath($targetPath)) {
            throw new InvalidArgumentException('"'.$targetPath.'" is not an absolute target path');
        }

        $this->targetPath = $targetPath;

        return $this;
    }

    public function getBypassCache(): bool
    {
        return $this->bypassCache;
    }

    public function setBypassCache(bool $bypassCache): self
    {
        $this->bypassCache = $bypassCache;

        return $this;
    }

    public function getSkipIfDimensionsMatch(): bool
    {
        return $this->skipIfDimensionsMatch;
    }

    public function setSkipIfDimensionsMatch(bool $skipIfDimensionsMatch): self
    {
        $this->skipIfDimensionsMatch = $skipIfDimensionsMatch;

        return $this;
    }

    public function getPreserveCopyrightMetadata(): array
    {
        return $this->preserveCopyrightMetadata;
    }

    public function setPreserveCopyrightMetadata(array $preserveCopyrightMetadata): self
    {
        $this->preserveCopyrightMetadata = $preserveCopyrightMetadata;

        return $this;
    }
}
