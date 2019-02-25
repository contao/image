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

use Symfony\Component\Filesystem\Filesystem;

class ResizeOptions implements ResizeOptionsInterface
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
     * {@inheritdoc}
     */
    public function getImagineOptions(): array
    {
        return $this->imagineOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function setImagineOptions(array $imagineOptions): ResizeOptionsInterface
    {
        $this->imagineOptions = $imagineOptions;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargetPath(): ?string
    {
        return $this->targetPath;
    }

    /**
     * {@inheritdoc}
     */
    public function setTargetPath(?string $targetPath): ResizeOptionsInterface
    {
        if (null !== $targetPath && !(new Filesystem())->isAbsolutePath($targetPath)) {
            throw new \InvalidArgumentException('"'.$targetPath.'" is not an absolute target path');
        }

        $this->targetPath = $targetPath;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getBypassCache(): bool
    {
        return $this->bypassCache;
    }

    /**
     * {@inheritdoc}
     */
    public function setBypassCache(bool $bypassCache): ResizeOptionsInterface
    {
        $this->bypassCache = $bypassCache;

        return $this;
    }
}
