<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image\Test;

use Contao\Image\ImageDimensions;

/**
 * Tests the ImageDimensions class.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class ImageDimensionsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $dimensions = new ImageDimensions($this->getMock('Imagine\Image\BoxInterface'));

        $this->assertInstanceOf('Contao\Image\ImageDimensions', $dimensions);
        $this->assertInstanceOf('Contao\Image\ImageDimensionsInterface', $dimensions);
    }

    /**
     * Tests the getSize() method.
     */
    public function testGetSize()
    {
        $size = $this->getMock('Imagine\Image\BoxInterface');
        $dimensions = new ImageDimensions($size);

        $this->assertSame($size, $dimensions->getSize());
    }

    /**
     * Tests the isRelative() method.
     */
    public function testIsRelative()
    {
        $size = $this->getMock('Imagine\Image\BoxInterface');

        $dimensions = new ImageDimensions($size);
        $this->assertFalse($dimensions->isRelative());

        $dimensions = new ImageDimensions($size, true);
        $this->assertTrue($dimensions->isRelative());

        $size = $this->getMock('Contao\ImagineSvg\RelativeBoxInterface');

        $dimensions = new ImageDimensions($size);
        $this->assertTrue($dimensions->isRelative());

        $dimensions = new ImageDimensions($size, false);
        $this->assertFalse($dimensions->isRelative());
    }

    /**
     * Tests the isUndefined() method.
     */
    public function testIsUndefined()
    {
        $size = $this->getMock('Imagine\Image\BoxInterface');

        $dimensions = new ImageDimensions($size);
        $this->assertFalse($dimensions->isUndefined());

        $dimensions = new ImageDimensions($size, null, true);
        $this->assertTrue($dimensions->isUndefined());

        $size = $this->getMock('Contao\ImagineSvg\UndefinedBoxInterface');

        $dimensions = new ImageDimensions($size);
        $this->assertTrue($dimensions->isUndefined());

        $dimensions = new ImageDimensions($size, null, false);
        $this->assertFalse($dimensions->isUndefined());
    }
}
