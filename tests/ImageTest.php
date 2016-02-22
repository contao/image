<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image\Test;

use Contao\Image\Image;
use Contao\Image\ImageDimensions;
use Contao\Image\ImportantPart;
use Imagine\Image\Box;
use Imagine\Image\Point;

/**
 * Tests the Image class.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class ImageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Create an image instance helper.
     *
     * @param ImagineInterface $imagine
     * @param Filesystem       $filesystem
     * @param string           $path
     *
     * @return Image
     */
    private function createImage($imagine = null, $filesystem = null, $path = 'dummy.jpg')
    {
        if (null === $imagine) {
            $imagine = $this->getMock('Imagine\Image\ImagineInterface');
        }

        if (null === $filesystem) {
            $filesystem = $this->getMock('Symfony\Component\Filesystem\Filesystem');
            $filesystem->method('exists')->willReturn(true);
        }

        return new Image($imagine, $filesystem, $path);
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\Image\Image', $this->createImage());
    }

    /**
     * Tests the object instantiation with a missing image.
     *
     * @expectedException InvalidArgumentException
     */
    public function testInstantiationMissingFiles()
    {
        $filesystem = $this->getMock('Symfony\Component\Filesystem\Filesystem');
        $filesystem->method('exists')->willReturn(false);

        $this->createImage(null, $filesystem);
    }

    /**
     * Tests the getPath() method.
     */
    public function testGetPath()
    {
        $image = $this->createImage(null, null, '/path/filename.jpeg');

        $this->assertEquals('/path/filename.jpeg', $image->getPath());
    }

    /**
     * Tests the getUrl() method.
     */
    public function testGetUrl()
    {
        $image = $this->createImage(null, null, '/path/to/a/filename with special&chars.jpeg');

        $this->assertEquals('path/to/a/filename%20with%20special%26chars.jpeg', $image->getUrl(''));
        $this->assertEquals('to/a/filename%20with%20special%26chars.jpeg', $image->getUrl('/path'));
        $this->assertEquals('a/filename%20with%20special%26chars.jpeg', $image->getUrl('/path/to'));
        $this->assertEquals('filename%20with%20special%26chars.jpeg', $image->getUrl('/path/to/a'));

        $this->setExpectedException('InvalidArgumentException');
        $image->getUrl('/path/t');
    }

    /**
     * Tests the getDimensions() method.
     */
    public function testGetDimensions()
    {
        $imagine = $this->getMock('Imagine\Image\ImagineInterface');
        $imagineImage = $this->getMock('Imagine\Image\ImageInterface');
        $imagine->method('open')->willReturn($imagineImage);
        $imagineImage->method('getSize')->willReturn(new Box(100, 100));

        $image = $this->createImage($imagine);

        $this->assertEquals(
            new ImageDimensions(new Box(100, 100)),
            $image->getDimensions()
        );
    }

    /**
     * Tests the getImportantPart() method.
     */
    public function testGetImportantPart()
    {
        $imagine = $this->getMock('Imagine\Image\ImagineInterface');
        $imagineImage = $this->getMock('Imagine\Image\ImageInterface');
        $imagine->method('open')->willReturn($imagineImage);
        $imagineImage->method('getSize')->willReturn(new Box(100, 100));

        $image = $this->createImage($imagine);

        $this->assertEquals(
            new ImportantPart(new Point(0, 0), new Box(100, 100)),
            $image->getImportantPart()
        );

        $image->setImportantPart(
            new ImportantPart(new Point(10, 10), new Box(80, 80))
        );

        $this->assertEquals(
            new ImportantPart(new Point(10, 10), new Box(80, 80)),
            $image->getImportantPart()
        );
    }
}
