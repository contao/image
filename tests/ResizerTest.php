<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image\Test;

use Contao\Image\Resizer;
use Contao\Image\ImageDimensions;
use Contao\Image\ResizeCalculator;
use Contao\Image\ResizeCoordinates;
use Contao\ImagineSvg\Imagine as SvgImagine;
use Contao\ImagineSvg\UndefinedBox;
use Symfony\Component\Filesystem\Filesystem;
use Imagine\Gd\Imagine as GdImagine;
use Imagine\Image\Box;
use Imagine\Image\Point;

/**
 * Tests the Resizer class.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class ResizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $rootDir;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->rootDir = __DIR__ . '/tmp';
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        if (file_exists($this->rootDir)) {
            (new Filesystem())->remove($this->rootDir);
        }
    }

    /**
     * Create a resizer instance helper.
     *
     * @param ResizeCalculator         $calculator
     * @param Filesystem               $filesystem
     * @param string                   $path
     *
     * @return Resizer
     */
    private function createResizer($calculator = null, $filesystem = null, $path = null)
    {
        if (null === $calculator) {
            $calculator = $this->getMock('Contao\Image\ResizeCalculator');
        }

        if (null === $filesystem) {
            $filesystem = new Filesystem();
        }

        if (null === $path) {
            $path = $this->rootDir;
        }

        return new Resizer($calculator, $filesystem, $path);
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\Image\Resizer', $this->createResizer());
    }

    /**
     * Tests the resize() method.
     */
    public function testResize()
    {
        $calculator = $this->getMock('Contao\Image\ResizeCalculator');
        $calculator->method('calculate')->willReturn(new ResizeCoordinates(
            new Box(100, 100),
            new Point(0, 0),
            new Box(100, 100)
        ));

        $resizer = $this->createResizer($calculator);

        if (!is_dir($this->rootDir)) {
            mkdir($this->rootDir, 0777, true);
        }

        (new GdImagine())
            ->create(new Box(100, 100))
            ->save($this->rootDir . '/dummy.jpg');

        $image = $this->getMockBuilder('Contao\Image\Image')
             ->disableOriginalConstructor()
             ->getMock();
        $image->method('getDimensions')->willReturn(new ImageDimensions(new Box(100, 100)));
        $image->method('getPath')->willReturn($this->rootDir . '/dummy.jpg');
        $image->method('getImagine')->willReturn(new GdImagine());

        $configuration = $this->getMock('Contao\Image\ResizeConfiguration');

        $resizedImage = $resizer->resize($image, $configuration);

        $this->assertEquals(new ImageDimensions(new Box(100, 100)), $resizedImage->getDimensions());
        $this->assertRegExp('(/[0-9a-f]/dummy-[0-9a-f]{8}.jpg$)', $resizedImage->getPath());
        unlink($resizedImage->getPath());

        $resizedImage = $resizer->resize($image, $configuration, [], $this->rootDir . '/target-path.jpg');

        $this->assertEquals(new ImageDimensions(new Box(100, 100)), $resizedImage->getDimensions());
        $this->assertEquals($this->rootDir . '/target-path.jpg', $resizedImage->getPath());
        unlink($resizedImage->getPath());
    }

    /**
     * Tests the resize() method for SVG files.
     */
    public function testResizeSvg()
    {
        $xml = '<?xml version="1.0"?>' .
            '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="100" height="100"></svg>';

        if (!is_dir($this->rootDir)) {
            mkdir($this->rootDir, 0777, true);
        }
        file_put_contents($this->rootDir . '/dummy.svg', $xml);

        $calculator = $this->getMock('Contao\Image\ResizeCalculator');
        $calculator->method('calculate')->willReturn(new ResizeCoordinates(
            new Box(100, 100),
            new Point(0, 0),
            new Box(100, 100)
        ));

        $resizer = $this->createResizer($calculator);

        $image = $this->getMockBuilder('Contao\Image\Image')
             ->disableOriginalConstructor()
             ->getMock();
        $image->method('getDimensions')->willReturn(new ImageDimensions(new Box(100, 100)));
        $image->method('getPath')->willReturn($this->rootDir . '/dummy.svg');
        $image->method('getImagine')->willReturn(new SvgImagine());

        $configuration = $this->getMock('Contao\Image\ResizeConfiguration');

        $resizedImage = $resizer->resize($image, $configuration);

        $this->assertEquals(new ImageDimensions(new Box(100, 100)), $resizedImage->getDimensions());
        $this->assertRegExp('(/[0-9a-f]/dummy-[0-9a-f]{8}.svg$)', $resizedImage->getPath());
        unlink($resizedImage->getPath());

        $resizedImage = $resizer->resize($image, $configuration, [], $this->rootDir . '/target-path.svg');

        $this->assertEquals(new ImageDimensions(new Box(100, 100)), $resizedImage->getDimensions());
        $this->assertEquals($this->rootDir . '/target-path.svg', $resizedImage->getPath());
        unlink($resizedImage->getPath());
    }

    /**
     * Tests the resize() method.
     */
    public function testResizeCache()
    {
        $calculator = $this->getMock('Contao\Image\ResizeCalculator');
        $calculator->method('calculate')->willReturn(new ResizeCoordinates(
            new Box(100, 100),
            new Point(0, 0),
            new Box(100, 100)
        ));

        $resizer = $this->createResizer($calculator);

        if (!is_dir($this->rootDir)) {
            mkdir($this->rootDir, 0777, true);
        }

        (new GdImagine())
            ->create(new Box(100, 100))
            ->save($this->rootDir . '/dummy.jpg');

        $image = $this->getMockBuilder('Contao\Image\Image')
             ->disableOriginalConstructor()
             ->getMock();
        $image->method('getDimensions')->willReturn(new ImageDimensions(new Box(100, 100)));
        $image->method('getPath')->willReturn($this->rootDir . '/dummy.jpg');
        $image->method('getImagine')->willReturn(new GdImagine());

        $configuration = $this->getMock('Contao\Image\ResizeConfiguration');

        $resizedImage = $resizer->resize($image, $configuration);

        $this->assertEquals(new ImageDimensions(new Box(100, 100)), $resizedImage->getDimensions());
        $this->assertRegExp('(/[0-9a-f]/dummy-[0-9a-f]{8}.jpg$)', $resizedImage->getPath());

        $imagePath = $resizedImage->getPath();

        // Different cache file for testing
        (new GdImagine())
            ->create(new Box(200, 100))
            ->save($imagePath);

        // With cache
        $resizedImage = $resizer->resize($image, $configuration);

        $this->assertEquals($imagePath, $resizedImage->getPath());
        $this->assertEquals(200, getimagesize($imagePath)[0], 'Cache file should no be overwritten');

        // With cache and target path
        $targetPath = $this->rootDir . '/target-image.jpg';
        $resizedImage = $resizer->resize($image, $configuration, [], $targetPath);

        $this->assertEquals($targetPath, $resizedImage->getPath());
        $this->assertFileEquals($imagePath, $targetPath, 'Cache file should have been copied');

        // Without cache
        $resizedImage = $resizer->resize($image, $configuration, [], null, true);

        $this->assertEquals($imagePath, $resizedImage->getPath());
        $this->assertEquals(100, getimagesize($imagePath)[0]);
    }

    /**
     * Tests the resize() method.
     */
    public function testResizeUndefinedSize()
    {
        $imagePath = $this->rootDir . '/dummy.jpg';

        $resizer = $this->createResizer();

        if (!is_dir($this->rootDir)) {
            mkdir($this->rootDir, 0777, true);
        }

        file_put_contents($imagePath, '');

        $image = $this->getMockBuilder('Contao\Image\Image')
             ->disableOriginalConstructor()
             ->getMock();
        $image->method('getDimensions')->willReturn(new ImageDimensions(new UndefinedBox()));
        $image->method('getPath')->willReturn($imagePath);
        $image->method('getImagine')->willReturn(new GdImagine());

        $configuration = $this->getMock('Contao\Image\ResizeConfiguration');

        $resizedImage = $resizer->resize($image, $configuration);

        $this->assertEquals($imagePath, $resizedImage->getPath());
    }

    /**
     * Tests the resize() method.
     */
    public function testResizeEmptyConfig()
    {
        $imagePath = $this->rootDir . '/dummy.jpg';

        $resizer = $this->createResizer();

        if (!is_dir($this->rootDir)) {
            mkdir($this->rootDir, 0777, true);
        }

        file_put_contents($imagePath, '');

        $image = $this->getMockBuilder('Contao\Image\Image')
             ->disableOriginalConstructor()
             ->getMock();
        $image->method('getDimensions')->willReturn(new ImageDimensions(new Box(100, 100)));
        $image->method('getPath')->willReturn($imagePath);
        $image->method('getImagine')->willReturn(new GdImagine());

        $configuration = $this->getMock('Contao\Image\ResizeConfiguration');
        $configuration->method('isEmpty')->willReturn(true);

        $resizedImage = $resizer->resize($image, $configuration);

        $this->assertEquals($image->getPath(), $resizedImage->getPath());
        $this->assertNotSame($image, $resizedImage);
    }
}
