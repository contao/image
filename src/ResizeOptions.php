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
use Symfony\Component\Filesystem\Filesystem;

class ResizeOptions
{
    /**
     * @var array
     */
    private $imagineOptions = [];

    /**
     * @var ?string
     */
    private $targetPath;

    /**
     * @var bool
     */
    private $bypassCache = false;

    /**
     * @var bool
     */
    private $skipIfDimensionsMatch = false;

    /**
     * @var bool
     */
    private $preserveCopyrightMetadata = true;

    public function getImagineOptions(): array
    {
        return $this->imagineOptions;
    }

    public function setImagineOptions(array $imagineOptions): self
    {
        $this->imagineOptions = $imagineOptions;

        return $this;
    }

    public function getTargetPath(): ?string
    {
        return $this->targetPath;
    }

    public function setTargetPath(?string $targetPath): self
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

    public function getPreserveCopyrightMetadata(): bool
    {
        return $this->preserveCopyrightMetadata;
    }

    public function setPreserveCopyrightMetadata(bool $preserveCopyrightMetadata): self
    {
        $this->preserveCopyrightMetadata = $preserveCopyrightMetadata;

        return $this;
    }
}
