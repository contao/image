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

final class LegacyOptionsConverter
{
    public static function convert(ResizeOptions $options): ProcessingOptions
    {
        return (new ProcessingOptions())
            ->withEncoding((new EncodingOptions())->withAdapterOptions('imagine', $options->getImagineOptions()))
            ->withBypassCache($options->getBypassCache())
            ->withDestination($options->getTargetPath())
            ->withSkipIfDimensionsMatch($options->getSkipIfDimensionsMatch())
            ->withPreserveCopyrightMetadata($options->getPreserveCopyrightMetadata())
        ;
    }
}
