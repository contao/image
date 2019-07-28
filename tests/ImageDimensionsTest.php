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

use Contao\Image\ImageDimensions;
use Contao\ImagineSvg\RelativeBoxInterface;
use Contao\ImagineSvg\UndefinedBoxInterface;
use Imagine\Image\BoxInterface;
use PHPUnit\Framework\TestCase;

class ImageDimensionsTest extends TestCase
{
    public function testGetSize(): void
    {
        $size = $this->createMock(BoxInterface::class);
        $dimensions = new ImageDimensions($size);

        $this->assertSame($size, $dimensions->getSize());
    }

    public function testGetOrientation(): void
    {
        $size = $this->createMock(BoxInterface::class);
        $dimensions = new ImageDimensions($size, null, null, ImageDimensions::ORIENTATION_90);

        $this->assertSame(ImageDimensions::ORIENTATION_90, $dimensions->getOrientation());

        $this->expectException('InvalidArgumentException');

        new ImageDimensions($size, null, null, 0);
    }

    public function testIsRelative(): void
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

    public function testIsUndefined(): void
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
