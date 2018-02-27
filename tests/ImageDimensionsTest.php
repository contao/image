<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image\Tests;

use Contao\Image\ImageDimensions;
use Contao\ImagineSvg\RelativeBoxInterface;
use Contao\ImagineSvg\UndefinedBoxInterface;
use Imagine\Image\BoxInterface;
use PHPUnit\Framework\TestCase;

class ImageDimensionsTest extends TestCase
{
    public function testInstantiation()
    {
        $dimensions = new ImageDimensions($this->createMock(BoxInterface::class));

        $this->assertInstanceOf('Contao\Image\ImageDimensions', $dimensions);
        $this->assertInstanceOf('Contao\Image\ImageDimensionsInterface', $dimensions);
    }

    public function testGetSize()
    {
        $size = $this->createMock(BoxInterface::class);
        $dimensions = new ImageDimensions($size);

        $this->assertSame($size, $dimensions->getSize());
    }

    public function testIsRelative()
    {
        $size = $this->createMock(BoxInterface::class);

        $dimensions = new ImageDimensions($size);
        $this->assertFalse($dimensions->isRelative());

        $dimensions = new ImageDimensions($size, true);
        $this->assertTrue($dimensions->isRelative());

        $size = $this->createMock(RelativeBoxInterface::class);

        $dimensions = new ImageDimensions($size);
        $this->assertTrue($dimensions->isRelative());

        $dimensions = new ImageDimensions($size, false);
        $this->assertFalse($dimensions->isRelative());
    }

    public function testIsUndefined()
    {
        $size = $this->createMock(BoxInterface::class);

        $dimensions = new ImageDimensions($size);
        $this->assertFalse($dimensions->isUndefined());

        $dimensions = new ImageDimensions($size, null, true);
        $this->assertTrue($dimensions->isUndefined());

        $size = $this->createMock(UndefinedBoxInterface::class);

        $dimensions = new ImageDimensions($size);
        $this->assertTrue($dimensions->isUndefined());

        $dimensions = new ImageDimensions($size, null, false);
        $this->assertFalse($dimensions->isUndefined());
    }
}
