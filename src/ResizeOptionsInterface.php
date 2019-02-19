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

interface ResizeOptionsInterface
{
    /**
     * Returns the imagine options.
     */
    public function getImagineOptions(): array;

    /**
     * Returns the imagine options.
     */
    public function setImagineOptions(array $imagineOptions): self;

    /**
     * Returns the target path.
     */
    public function getTargetPath(): ?string;

    /**
     * Sets the target path.
     */
    public function setTargetPath(?string $targetPath): self;

    /**
     * Returns the bypass cache flag.
     */
    public function getBypassCache(): bool;

    /**
     * Sets the bypass cache flag.
     */
    public function setBypassCache(bool $bypassCache): self;
}
