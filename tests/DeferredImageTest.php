<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image\Tests;

use Contao\Image\DeferredImage;
use Contao\Image\ImageDimensionsInterface;
use Imagine\Image\ImagineInterface;
use PHPUnit\Framework\TestCase;

class DeferredImageTest extends TestCase
{
    public function testGetters(): void
    {
        $path = '/path/to/image.jpg';
        $imagine = $this->createMock(ImagineInterface::class);
        $dimensions = $this->createMock(ImageDimensionsInterface::class);

        $image = new DeferredImage($path, $imagine, $dimensions);

        $this->assertSame($path, $image->getPath());
        $this->assertSame($imagine, $image->getImagine());
        $this->assertSame($dimensions, $image->getDimensions());
    }
}
