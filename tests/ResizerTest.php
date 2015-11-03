<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Image;

use Contao\CoreBundle\Test\TestCase;
use Contao\CoreBundle\Image\Resizer;
use Contao\CoreBundle\Image\ImageDimensions;
use Contao\CoreBundle\Image\ImportantPart;
use Contao\CoreBundle\Image\ResizeCoordinates;
use Symfony\Component\Filesystem\Filesystem;
use Imagine\Image\Box;
use Imagine\Image\Point;

/**
 * Tests the Resizer class.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class ResizerTest extends TestCase
{
    /**
     * Create a resizer instance helper
     *
     * @param ImagineInterface $imagine
     * @param Filesystem       $filesystem
     * @param string           $path
     *
     * @return Resizer
     */
    private function createResizer($calculator = null, $imagine = null, $filesystem = null, $path = null, $framework = null)
    {
        if (null === $calculator) {
            $calculator = $this->getMock('Contao\CoreBundle\Image\ResizeCalculator');
        }

        if (null === $imagine) {
            $imagine = $this->getMock('Imagine\Image\ImagineInterface');
        }

        if (null === $filesystem) {
            $filesystem = new Filesystem;
        }

        if (null === $path) {
            $path = $this->getRootDir() . '/system/tmp/images';
        }

        if (null === $framework) {
            $framework = $this->getMock('Contao\CoreBundle\Framework\ContaoFrameworkInterface');
        }

        return new Resizer($calculator, $imagine, $filesystem, $path, $framework);
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\\CoreBundle\\Image\\Resizer', $this->createResizer());
    }

    /**
     * Tests the resize() method.
     */
    public function testResize()
    {
        $calculator = $this->getMock('Contao\CoreBundle\Image\ResizeCalculator');
        $calculator->method('calculate')->willReturn(new ResizeCoordinates(
            new Box(100, 100),
            new Point(0, 0),
            new Box(100, 100)
        ));

        $resizer = $this->createResizer($calculator, new \Imagine\Gd\Imagine);

        $image = $this->getMockBuilder('Contao\CoreBundle\Image\Image')
             ->disableOriginalConstructor()
             ->getMock();
        $image->method('getDimensions')->willReturn(new ImageDimensions(new Box(100, 100)));
        $image->method('getPath')->willReturn($this->getRootDir() . '/images/dummy.jpg');

        $configuration = $this->getMock('Contao\CoreBundle\Image\ResizeConfiguration');

        $resizedImage = $resizer->resize($image, $configuration);

        $this->assertEquals(new ImageDimensions(new Box(100, 100)), $resizedImage->getDimensions());
        $this->assertRegExp('(/[0-9a-f]/dummy-[0-9a-f]{8}.jpg$)', $resizedImage->getPath());

        $resizedImage = $resizer->resize($image, $configuration, $this->getRootDir() . '/system/tmp/images/target-path.jpg');

        $this->assertEquals(new ImageDimensions(new Box(100, 100)), $resizedImage->getDimensions());
        $this->assertEquals($this->getRootDir() . '/system/tmp/images/target-path.jpg', $resizedImage->getPath());
    }
}
