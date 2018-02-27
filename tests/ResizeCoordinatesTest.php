<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image\Tests;

use Contao\Image\ResizeCoordinates;
use Imagine\Image\Box;
use Imagine\Image\BoxInterface;
use Imagine\Image\Point;
use Imagine\Image\PointInterface;
use PHPUnit\Framework\TestCase;

class ResizeCoordinatesTest extends TestCase
{
    public function testInstantiation()
    {
        $coordinates = new ResizeCoordinates(
            $this->createMock(BoxInterface::class),
            $this->createMock(PointInterface::class),
            $this->createMock(BoxInterface::class)
        );

        $this->assertInstanceOf('Contao\Image\ResizeCoordinates', $coordinates);
        $this->assertInstanceOf('Contao\Image\ResizeCoordinatesInterface', $coordinates);
    }

    public function testGetter()
    {
        $size = $this->createMock(BoxInterface::class);
        $cropStart = $this->createMock(PointInterface::class);
        $cropSize = $this->createMock(BoxInterface::class);
        $coordinates = new ResizeCoordinates($size, $cropStart, $cropSize);

        $this->assertSame($size, $coordinates->getSize());
        $this->assertSame($cropStart, $coordinates->getCropStart());
        $this->assertSame($cropSize, $coordinates->getCropSize());
    }

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

        $this->assertSame($hash1, $hash2);
        $this->assertNotSame($hash1, $hash3);
    }

    public function testIsEqualTo()
    {
        $coordinates = new ResizeCoordinates(new Box(200, 200), new Point(50, 50), new Box(100, 100));

        $this->assertTrue(
            $coordinates->isEqualTo(new ResizeCoordinates(new Box(200, 200), new Point(50, 50), new Box(100, 100)))
        );

        $this->assertFalse(
            $coordinates->isEqualTo(new ResizeCoordinates(new Box(200, 200), new Point(51, 50), new Box(100, 100)))
        );

        $this->assertFalse($coordinates->isEqualTo(new Box(200, 200)));

        $coordinates = new ResizeCoordinates(new Box(100, 100), new Point(0, 0), new Box(100, 100));
        $this->assertTrue($coordinates->isEqualTo(new Box(100, 100)));

        $this->expectException('InvalidArgumentException');

        /* @noinspection PhpParamsInspection */
        $coordinates->isEqualTo(new \stdClass());
    }
}
