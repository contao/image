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
use Contao\CoreBundle\Image\ResizeCalculator;
use Contao\CoreBundle\Image\ResizeCoordinates;
use Contao\CoreBundle\Image\ImagineSvg\Imagine as SvgImagine;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Symfony\Component\Filesystem\Filesystem;
use Imagine\Gd\Imagine as GdImagine;
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
     * Create a resizer instance helper.
     *
     * @param ResizeCalculator         $calculator
     * @param Filesystem               $filesystem
     * @param string                   $path
     * @param ContaoFrameworkInterface $framework
     *
     * @return Resizer
     */
    private function createResizer($calculator = null, $filesystem = null, $path = null, $framework = null)
    {
        if (null === $calculator) {
            $calculator = $this->getMock('Contao\CoreBundle\Image\ResizeCalculator');
        }

        if (null === $filesystem) {
            $filesystem = new Filesystem();
        }

        if (null === $path) {
            $path = $this->getRootDir() . '/system/tmp/images';
        }

        if (null === $framework) {
            $framework = $this->getMock('Contao\CoreBundle\Framework\ContaoFrameworkInterface');
        }

        return new Resizer($calculator, $filesystem, $path, $framework);
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

        $resizer = $this->createResizer($calculator);

        $image = $this->getMockBuilder('Contao\CoreBundle\Image\Image')
             ->disableOriginalConstructor()
             ->getMock();
        $image->method('getDimensions')->willReturn(new ImageDimensions(new Box(100, 100)));
        $image->method('getPath')->willReturn($this->getRootDir() . '/images/dummy.jpg');
        $image->method('getImagine')->willReturn(new GdImagine());

        $configuration = $this->getMock('Contao\CoreBundle\Image\ResizeConfiguration');

        $resizedImage = $resizer->resize($image, $configuration);

        $this->assertEquals(new ImageDimensions(new Box(100, 100)), $resizedImage->getDimensions());
        $this->assertRegExp('(/[0-9a-f]/dummy-[0-9a-f]{8}.jpg$)', $resizedImage->getPath());
        unlink($resizedImage->getPath());

        $resizedImage = $resizer->resize($image, $configuration, $this->getRootDir() . '/system/tmp/images/target-path.jpg');

        $this->assertEquals(new ImageDimensions(new Box(100, 100)), $resizedImage->getDimensions());
        $this->assertEquals($this->getRootDir() . '/system/tmp/images/target-path.jpg', $resizedImage->getPath());
        unlink($resizedImage->getPath());
    }

    /**
     * Tests the resize() method for SVG files.
     */
    public function testResizeSvg()
    {
        $xml = '<?xml version="1.0"?>' .
            '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="100" height="100"></svg>';

        if (!is_dir($this->getRootDir() . '/system/tmp/images')) {
            mkdir($this->getRootDir() . '/system/tmp/images', 0777, true);
        }
        file_put_contents($this->getRootDir() . '/system/tmp/images/dummy.svg', $xml);

        $calculator = $this->getMock('Contao\CoreBundle\Image\ResizeCalculator');
        $calculator->method('calculate')->willReturn(new ResizeCoordinates(
            new Box(100, 100),
            new Point(0, 0),
            new Box(100, 100)
        ));

        $resizer = $this->createResizer($calculator);

        $image = $this->getMockBuilder('Contao\CoreBundle\Image\Image')
             ->disableOriginalConstructor()
             ->getMock();
        $image->method('getDimensions')->willReturn(new ImageDimensions(new Box(100, 100)));
        $image->method('getPath')->willReturn($this->getRootDir() . '/system/tmp/images/dummy.svg');
        $image->method('getImagine')->willReturn(new SvgImagine());

        $configuration = $this->getMock('Contao\CoreBundle\Image\ResizeConfiguration');

        $resizedImage = $resizer->resize($image, $configuration);

        $this->assertEquals(new ImageDimensions(new Box(100, 100)), $resizedImage->getDimensions());
        $this->assertRegExp('(/[0-9a-f]/dummy-[0-9a-f]{8}.svg$)', $resizedImage->getPath());
        unlink($resizedImage->getPath());

        $resizedImage = $resizer->resize($image, $configuration, $this->getRootDir() . '/system/tmp/images/target-path.svg');

        $this->assertEquals(new ImageDimensions(new Box(100, 100)), $resizedImage->getDimensions());
        $this->assertEquals($this->getRootDir() . '/system/tmp/images/target-path.svg', $resizedImage->getPath());
        unlink($resizedImage->getPath());

        unlink($this->getRootDir() . '/system/tmp/images/dummy.svg');
    }

    /**
     * Tests the resize() method.
     */
    public function testResizeCache()
    {
        $calculator = $this->getMock('Contao\CoreBundle\Image\ResizeCalculator');
        $calculator->method('calculate')->willReturn(new ResizeCoordinates(
            new Box(100, 100),
            new Point(0, 0),
            new Box(100, 100)
        ));

        $resizer = $this->createResizer($calculator);

        $image = $this->getMockBuilder('Contao\CoreBundle\Image\Image')
             ->disableOriginalConstructor()
             ->getMock();
        $image->method('getDimensions')->willReturn(new ImageDimensions(new Box(100, 100)));
        $image->method('getPath')->willReturn($this->getRootDir() . '/images/dummy.jpg');
        $image->method('getImagine')->willReturn(new GdImagine());

        $configuration = $this->getMock('Contao\CoreBundle\Image\ResizeConfiguration');

        $resizedImage = $resizer->resize($image, $configuration);

        $this->assertEquals(new ImageDimensions(new Box(100, 100)), $resizedImage->getDimensions());
        $this->assertRegExp('(/[0-9a-f]/dummy-[0-9a-f]{8}.jpg$)', $resizedImage->getPath());

        $imagePath = $resizedImage->getPath();

        // Empty cache file
        file_put_contents($imagePath, '');

        // With cache
        $resizedImage = $resizer->resize($image, $configuration);

        $this->assertEquals($imagePath, $resizedImage->getPath());
        $this->assertEquals(0, filesize($imagePath));

        // Without cache
        $resizedImage = $resizer->resize($image, $configuration, null, true);

        $this->assertEquals($imagePath, $resizedImage->getPath());
        $this->assertNotEquals(0, filesize($imagePath));

        unlink($imagePath);
    }
}
