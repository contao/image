<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image\Test;

use Contao\Image\ResizeCoordinates;
use Imagine\Image\Box;
use Imagine\Image\Point;

/**
 * Tests the ResizeCoordinates class.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class ResizeCoordinatesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $coordinates = new ResizeCoordinates(
            $this->getMock('Imagine\Image\BoxInterface'),
            $this->getMock('Imagine\Image\PointInterface'),
            $this->getMock('Imagine\Image\BoxInterface')
        );

        $this->assertInstanceOf('Contao\Image\ResizeCoordinates', $coordinates);
        $this->assertInstanceOf('Contao\Image\ResizeCoordinatesInterface', $coordinates);
    }

    /**
     * Tests the getter methods.
     */
    public function testGetter()
    {
        $size = $this->getMock('Imagine\Image\BoxInterface');
        $cropStart = $this->getMock('Imagine\Image\PointInterface');
        $cropSize = $this->getMock('Imagine\Image\BoxInterface');
        $coordinates = new ResizeCoordinates($size, $cropStart, $cropSize);

        $this->assertSame($size, $coordinates->getSize());
        $this->assertSame($cropStart, $coordinates->getCropStart());
        $this->assertSame($cropSize, $coordinates->getCropSize());
    }

    /**
     * Tests the getHash() method.
     */
    public function testGetHash()
    {
        $coordinates1 = new ResizeCoordinates(new Box(200, 200), new Point(50, 50), new Box(100, 100));
        $coordinates2 = new ResizeCoordinates(new Box(200.1, 200.1), new Point(50.1, 50.1), new Box(100.1, 100.1));
        $coordinates3 = new ResizeCoordinates(new Box(201, 201), new Point(50, 50), new Box(100, 100));

        $hash1 = $coordinates1->getHash();
        $hash2 = $coordinates2->getHash();
        $hash3 = $coordinates3->getHash();

        $this->assertInternalType('string', $hash1);
        $this->assertInternalType('string', $hash2);
        $this->assertInternalType('string', $hash3);

        $this->assertEquals($hash1, $hash2);
        $this->assertNotEquals($hash1, $hash3);
    }

    /**
     * Tests the isEqualTo() method.
     */
    public function testIsEqualTo()
    {
        $coordinates = new ResizeCoordinates(new Box(200, 200), new Point(50, 50), new Box(100, 100));

        $this->assertTrue($coordinates->isEqualTo(
            new ResizeCoordinates(new Box(200, 200), new Point(50, 50), new Box(100, 100))
        ));

        $this->assertFalse($coordinates->isEqualTo(
            new ResizeCoordinates(new Box(200, 200), new Point(51, 50), new Box(100, 100))
        ));

        $this->assertFalse($coordinates->isEqualTo(new Box(200, 200)));

        $coordinates = new ResizeCoordinates(new Box(100, 100), new Point(0, 0), new Box(100, 100));
        $this->assertTrue($coordinates->isEqualTo(new Box(100, 100)));

        $this->setExpectedException('InvalidArgumentException');
        $coordinates->isEqualTo(new \stdClass);
    }
}
