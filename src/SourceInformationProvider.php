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

final class SourceInformationProvider
{
    public function __construct(private SourceInformationStorageInterface $storage, private ImageInspectorInterface $inspector)
    {
    }

    public function get(ImageSourceInterface $source, SourceVersion $version): ImageInformation
    {
        if (null !== $information = $this->storage->get($version)) {
            return $information;
        }
        $lock = $this->storage->lock($version);

        try {
            if (null !== $information = $this->storage->get($version)) {
                return $information;
            }
            $version->assertMatches($source);
            $information = $this->inspector->inspect($source);
            $version->assertMatches($source);
            $this->storage->put($version, $information);

            return $information;
        } finally {
            $lock->release();
        }
    }
}
