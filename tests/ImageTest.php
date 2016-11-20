<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image\Test;

use Contao\Image\Image;
use Contao\Image\ImageDimensions;
use Contao\Image\ImportantPart;
use Exception;
use Imagine\Gd\Imagine as GdImagine;
use Imagine\Image\Box;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Point;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Tests the Image class.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class ImageTest extends \PHPUnit_Framework_TestCase
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
        $this->rootDir = __DIR__.'/tmp';
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
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $image = $this->createImage();

        $this->assertInstanceOf('Contao\Image\Image', $image);
        $this->assertInstanceOf('Contao\Image\ImageInterface', $image);
    }

    /**
     * Tests the constructor with a non existent path.
     */
    public function testConstructorNonExistentPath()
    {
        $this->setExpectedException('InvalidArgumentException', '/path/to/non/existent/file.jpg does not exist');

        new Image('/path/to/non/existent/file.jpg', $this->getMock('Imagine\Image\ImagineInterface'));
    }

    /**
     * Tests the constructor with a directory as path.
     */
    public function testConstructorDirPath()
    {
        $this->setExpectedException('InvalidArgumentException', __DIR__.' is a directory');

        $this->createImage(__DIR__);
    }

    /**
     * Tests the object instantiation with a missing image.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testInstantiationMissingFiles()
    {
        $filesystem = $this->getMock('Symfony\Component\Filesystem\Filesystem');

        $filesystem
            ->method('exists')
            ->willReturn(false)
        ;

        $this->createImage(null, null, $filesystem);
    }

    /**
     * Tests the getPath() method.
     */
    public function testGetPath()
    {
        $image = $this->createImage('/path/filename.jpeg');

        $this->assertEquals('/path/filename.jpeg', $image->getPath());
    }

    /**
     * Tests the getUrl() method.
     */
    public function testGetUrl()
    {
        $image = $this->createImage('C:\path\to\a\filename with special&<>"\'%2Fchars.jpeg');

        $this->assertEquals('path/to/a/filename%20with%20special%26%3C%3E%22%27%252Fchars.jpeg', $image->getUrl('C:/'));
        $this->assertEquals('to/a/filename%20with%20special%26%3C%3E%22%27%252Fchars.jpeg', $image->getUrl('C:/path'));
        $this->assertEquals('a/filename%20with%20special%26%3C%3E%22%27%252Fchars.jpeg', $image->getUrl('C:/path/to'));
        $this->assertEquals('filename%20with%20special%26%3C%3E%22%27%252Fchars.jpeg', $image->getUrl('C:/path/to/a'));

        $image = $this->createImage('/path/to/a/filename with special&<>"\'%2Fchars.jpeg');

        $this->assertEquals('path/to/a/filename%20with%20special%26%3C%3E%22%27%252Fchars.jpeg', $image->getUrl('/'));
        $this->assertEquals('to/a/filename%20with%20special%26%3C%3E%22%27%252Fchars.jpeg', $image->getUrl('/path'));
        $this->assertEquals('a/filename%20with%20special%26%3C%3E%22%27%252Fchars.jpeg', $image->getUrl('/path/to'));
        $this->assertEquals('filename%20with%20special%26%3C%3E%22%27%252Fchars.jpeg', $image->getUrl('/path/to/a'));

        $this->setExpectedException('InvalidArgumentException');

        $image->getUrl('/path/t');
    }

    /**
     * Tests the getUrl() method with relative path components.
     */
    public function testGetUrlRealtivePath()
    {
        foreach ([
            '/path/to/a/file.png',
            '/path/to/a/subdir/../file.png',
            '/path/subdir/../to/a/file.png',
        ] as $imagePath) {
            $image = $this->createImage($imagePath);

            $this->assertEquals('path/to/a/file.png', $image->getUrl('/'));
            $this->assertEquals('to/a/file.png', $image->getUrl('/path'));
            $this->assertEquals('file.png', $image->getUrl('/path/to/a'));
            $this->assertEquals('file.png', $image->getUrl('/path/to/a/subdir/..'));
            $this->assertEquals('file.png', $image->getUrl('/path/to/subdir/../a'));
            $this->assertEquals('file.png', $image->getUrl('/path/subdir/../to/a'));
            $this->assertEquals(
                'https://example.com/images/to/a/file.png',
                $image->getUrl('/path', 'https://example.com/images/')
            );
        }

        foreach ([
            'C:\path\to\a\file.png',
            'C:\path\to\a/subdir\..\file.png',
            'C:\path/subdir\..\to\a/file.png',
        ] as $imagePath) {
            $image = $this->createImage($imagePath);

            $this->assertEquals('path/to/a/file.png', $image->getUrl('C:\\'));
            $this->assertEquals('to/a/file.png', $image->getUrl('C:\path'));
            $this->assertEquals('to/a/file.png', $image->getUrl('C:/path'));
            $this->assertEquals('file.png', $image->getUrl('C:\path\to\a'));
            $this->assertEquals('file.png', $image->getUrl('C:\path/to/a'));
            $this->assertEquals('file.png', $image->getUrl('C:\path\to\a\subdir\..'));
            $this->assertEquals('file.png', $image->getUrl('C:\path\to\subdir\..\a'));
            $this->assertEquals('file.png', $image->getUrl('C:\path\subdir\..\to\a'));
            $this->assertEquals('file.png', $image->getUrl('C:\path\subdir/../to\a'));
            $this->assertEquals(
                'https://example.com/images/to/a/file.png',
                $image->getUrl('C:\path', 'https://example.com/images/')
            );
        }

        $image = $this->createImage('C:\path/subdir\..\to\a/file.png');

        $this->setExpectedException('InvalidArgumentException');

        $image->getUrl('C:\path/subdir');
    }

    /**
     * Tests the getDimensions() method.
     */
    public function testGetDimensions()
    {
        $imagine = $this->getMock('Imagine\Image\ImagineInterface');
        $imagineImage = $this->getMock('Imagine\Image\ImageInterface');

        $imagine
            ->method('open')
            ->willReturn($imagineImage)
        ;

        $imagineImage
            ->method('getSize')
            ->willReturn(new Box(100, 100))
        ;

        $image = $this->createImage(null, $imagine);

        $this->assertEquals(new ImageDimensions(new Box(100, 100)), $image->getDimensions());
    }

    /**
     * Tests the getDimensions() method determines the dimensions without
     * Imagine and by only reading the file partially.
     */
    public function testGetDimensionsPartialFile()
    {
        if (!is_dir($this->rootDir)) {
            mkdir($this->rootDir, 0777, true);
        }

        $image = (new GdImagine())
            ->create(new Box(1000, 1000))
            ->get('jpg')
        ;

        // Only store the first 500 bytes of the image
        file_put_contents($this->rootDir.'/dummy.jpg', substr($image, 0, 500));

        $image = $this->createImage($this->rootDir.'/dummy.jpg');

        $this->assertEquals(new ImageDimensions(new Box(1000, 1000)), $image->getDimensions());
    }

    /**
     * Tests the getDimensions() method determines the SVG dimensions without
     * Imagine and by only reading the file partially.
     */
    public function testGetDimensionsPartialFileSvg()
    {
        $imagine = $this->getMock('Contao\ImagineSvg\Imagine');

        if (!is_dir($this->rootDir)) {
            mkdir($this->rootDir, 0777, true);
        }

        // Only store a partial SVG file without an end tag
        file_put_contents($this->rootDir.'/dummy.svg', '<svg width="1000" height="1000">');

        $image = $this->createImage($this->rootDir.'/dummy.svg', $imagine);

        $this->assertEquals(new ImageDimensions(new Box(1000, 1000)), $image->getDimensions());
    }

    /**
     * Tests the getDimensions() method handles invalid SVG images.
     */
    public function testGetDimensionsInvalidSvg()
    {
        if (!is_dir($this->rootDir)) {
            mkdir($this->rootDir, 0777, true);
        }

        file_put_contents($this->rootDir.'/dummy.svg', '<nosvg width="1000" height="1000"></nosvg>');

        $imagine = $this->getMock('Contao\ImagineSvg\Imagine');

        $imagine
            ->method('open')
            ->willThrowException(new Exception)
        ;

        $image = $this->createImage($this->rootDir.'/dummy.svg', $imagine);

        $this->setExpectedException('Exception');

        $image->getDimensions();
    }

    /**
     * Tests the getDimensions() method uses the dimensions cache.
     */
    public function testGetDimensionsFromCacheHit()
    {
        if (!is_dir($this->rootDir)) {
            mkdir($this->rootDir, 0777, true);
        }

        file_put_contents($this->rootDir.'/dummy.jpg', '');

        $cache = $this->getMock('Psr\Cache\CacheItemPoolInterface');
        $cacheItem = $this->getMock('Psr\Cache\CacheItemInterface');
        $dimensions = $this
            ->getMockBuilder('Contao\Image\ImageDimensionsInterface')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $cache
            ->method('getItem')
            ->with($this->matchesRegularExpression('/^[a-z0-9_.]{1,64}$/i'))
            ->willReturn($cacheItem)
        ;

        $cacheItem
            ->method('get')
            ->willReturn($dimensions)
        ;

        $cacheItem
            ->expects($this->never())
            ->method('set')
            ->willReturn($dimensions)
        ;

        $cacheItem
            ->method('isHit')
            ->willReturn(true)
        ;

        $image = $this->createImage($this->rootDir.'/dummy.jpg', null, null, $cache);

        $this->assertSame($dimensions, $image->getDimensions());
    }

    /**
     * Tests the getDimensions() method uses the dimensions cache.
     */
    public function testGetDimensionsFromCacheMiss()
    {
        if (!is_dir($this->rootDir)) {
            mkdir($this->rootDir, 0777, true);
        }

        file_put_contents($this->rootDir.'/dummy.jpg', '');

        $imagine = $this->getMock('Imagine\Image\ImagineInterface');
        $imagineImage = $this->getMock('Imagine\Image\ImageInterface');
        $cache = $this->getMock('Psr\Cache\CacheItemPoolInterface');
        $cacheItem = $this->getMock('Psr\Cache\CacheItemInterface');

        $imagine
            ->method('open')
            ->willReturn($imagineImage)
        ;

        $imagineImage
            ->method('getSize')
            ->willReturn(new Box(100, 100))
        ;

        $cache
            ->expects($this->once())
            ->method('getItem')
            ->with($this->matchesRegularExpression('/^[a-z0-9_.]{1,64}$/i'))
            ->willReturn($cacheItem)
        ;

        $cache
            ->expects($this->once())
            ->method('saveDeferred')
            ->with($cacheItem)
            ->willReturn(true)
        ;

        $cacheItem
            ->method('get')
            ->willReturn(null)
        ;

        $cacheItem
            ->method('isHit')
            ->willReturn(false)
        ;

        $cacheItem
            ->expects($this->once())
            ->method('set')
            ->with($this->equalTo(new ImageDimensions(new Box(100, 100))))
            ->willReturn($cacheItem)
        ;

        $image = $this->createImage($this->rootDir.'/dummy.jpg', $imagine, null, $cache);

        $this->assertEquals(new ImageDimensions(new Box(100, 100)), $image->getDimensions());
    }

    /**
     * Tests the getImportantPart() method.
     */
    public function testGetImportantPart()
    {
        $imagine = $this->getMock('Imagine\Image\ImagineInterface');
        $imagineImage = $this->getMock('Imagine\Image\ImageInterface');

        $imagine
            ->method('open')
            ->willReturn($imagineImage)
        ;

        $imagineImage
            ->method('getSize')
            ->willReturn(new Box(100, 100))
        ;

        $image = $this->createImage(null, $imagine);

        $this->assertEquals(new ImportantPart(new Point(0, 0), new Box(100, 100)), $image->getImportantPart());

        $image->setImportantPart(new ImportantPart(new Point(10, 10), new Box(80, 80)));

        $this->assertEquals(new ImportantPart(new Point(10, 10), new Box(80, 80)), $image->getImportantPart());
    }

    /**
     * Creates an image instance helper.
     *
     * @param string           $path
     * @param ImagineInterface $imagine
     * @param Filesystem       $filesystem
     *
     * @return Image
     */
    private function createImage($path = null, $imagine = null, $filesystem = null, $dimensionsCache = null)
    {
        if (null === $path) {
            $path = 'dummy.jpg';
        }

        if (null === $imagine) {
            $imagine = $this->getMock('Imagine\Image\ImagineInterface');
        }

        if (null === $filesystem) {
            $filesystem = $this->getMock('Symfony\Component\Filesystem\Filesystem');

            $filesystem
                ->method('exists')
                ->willReturn(true)
            ;
        }

        $image = new Image($path, $imagine, $filesystem);

        if (null !== $dimensionsCache) {
            $image->setDimensionsCache($dimensionsCache);
        }

        return $image;
    }
}
