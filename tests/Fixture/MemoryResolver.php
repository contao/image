<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image\Tests\Fixture;

use Contao\Image\ImageSourceInterface;
use Contao\Image\ImageSourceResolverInterface;
use Contao\Image\SourceReference;
use Contao\Image\SourceVersion;

final class MemoryResolver implements ImageSourceResolverInterface
{
    public function __construct(public ImageSourceInterface $source)
    {
    }

    public function resolve(SourceReference $reference, ?string $version = null): ImageSourceInterface
    {
        if (null !== $version) {
            (new SourceVersion($reference, $version))->assertMatches($this->source);
        }

        return $this->source;
    }
}
