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

final class FilesystemImageConfiguration
{
    private string $namespace = 'imagine-v1';
    private ImageInspectorInterface $inspector;

    public function __construct(private string $cacheDirectory, private string $urlPrefix, private string $secret)
    {
        $this->inspector = new StreamImageInspector();
    }

    public function getCacheDirectory(): string
    {
        return $this->cacheDirectory;
    }

    public function getUrlPrefix(): string
    {
        return $this->urlPrefix;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getInspector(): ImageInspectorInterface
    {
        return $this->inspector;
    }

    public function withNamespace(string $namespace): self
    {
        $copy = clone $this;
        $copy->namespace = $namespace;

        return $copy;
    }

    public function withInspector(ImageInspectorInterface $inspector): self
    {
        $copy = clone $this;
        $copy->inspector = $inspector;

        return $copy;
    }
}
