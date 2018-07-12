<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image\Tests;

use Contao\Image\Image;
use Contao\Image\ImageDimensions;
use Contao\Image\ImportantPart;
use Contao\ImagineSvg\Imagine;
use Exception;
use Imagine\Gd\Imagine as GdImagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Point;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class ImageTest extends TestCase
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
        parent::setUp();

        $this->rootDir = __DIR__.'/tmp';
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        parent::tearDown();

        if (file_exists($this->rootDir)) {
            (new Filesystem())->remove($this->rootDir);
        }
    }

    public function testInstantiation()
    {
        $image = $this->createImage();

        $this->assertInstanceOf('Contao\Image\Image', $image);
        $this->assertInstanceOf('Contao\Image\ImageInterface', $image);
    }

    public function testConstructorNonExistentPath()
    {
        $this->expectException('InvalidArgumentException');

        new Image('/path/to/non/existent/file.jpg', $this->createMock(ImagineInterface::class));
    }

    public function testConstructorDirPath()
    {
        $this->expectException('InvalidArgumentException');
        $this->createImage(__DIR__);
    }

    public function testInstantiationMissingFiles()
    {
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem
            ->method('exists')
            ->willReturn(false)
        ;

        $this->expectException('InvalidArgumentException');
        $this->createImage(null, null, $filesystem);
    }

    public function testGetPath()
    {
        $image = $this->createImage('/path/filename.jpeg');

        $this->assertSame('/path/filename.jpeg', $image->getPath());
    }

    public function testGetUrl()
    {
        $image = $this->createImage('C:\path\to\a\filename with special&<>"\'%2Fchars.jpeg');

        $this->assertSame('path/to/a/filename%20with%20special%26%3C%3E%22%27%252Fchars.jpeg', $image->getUrl('C:/'));
        $this->assertSame('to/a/filename%20with%20special%26%3C%3E%22%27%252Fchars.jpeg', $image->getUrl('C:/path'));
        $this->assertSame('a/filename%20with%20special%26%3C%3E%22%27%252Fchars.jpeg', $image->getUrl('C:/path/to'));
        $this->assertSame('filename%20with%20special%26%3C%3E%22%27%252Fchars.jpeg', $image->getUrl('C:/path/to/a'));

        $image = $this->createImage('/path/to/a/filename with special&<>"\'%2Fchars.jpeg');

        $this->assertSame('path/to/a/filename%20with%20special%26%3C%3E%22%27%252Fchars.jpeg', $image->getUrl('/'));
        $this->assertSame('to/a/filename%20with%20special%26%3C%3E%22%27%252Fchars.jpeg', $image->getUrl('/path'));
        $this->assertSame('a/filename%20with%20special%26%3C%3E%22%27%252Fchars.jpeg', $image->getUrl('/path/to'));
        $this->assertSame('filename%20with%20special%26%3C%3E%22%27%252Fchars.jpeg', $image->getUrl('/path/to/a'));

        $this->expectException('InvalidArgumentException');

        $image->getUrl('/path/t');
    }

    public function testGetUrlRealtivePath()
    {
        foreach ([
            '/path/to/a/file.png',
            '/path/to/a/subdir/../file.png',
            '/path/subdir/../to/a/file.png',
        ] as $imagePath) {
            $image = $this->createImage($imagePath);

            $this->assertSame('path/to/a/file.png', $image->getUrl('/'));
            $this->assertSame('to/a/file.png', $image->getUrl('/path'));
            $this->assertSame('file.png', $image->getUrl('/path/to/a'));
            $this->assertSame('file.png', $image->getUrl('/path/to/a/subdir/..'));
            $this->assertSame('file.png', $image->getUrl('/path/to/subdir/../a'));
            $this->assertSame('file.png', $image->getUrl('/path/subdir/../to/a'));
            $this->assertSame(
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

            $this->assertSame('path/to/a/file.png', $image->getUrl('C:\\'));
            $this->assertSame('to/a/file.png', $image->getUrl('C:\path'));
            $this->assertSame('to/a/file.png', $image->getUrl('C:/path'));
            $this->assertSame('file.png', $image->getUrl('C:\path\to\a'));
            $this->assertSame('file.png', $image->getUrl('C:\path/to/a'));
            $this->assertSame('file.png', $image->getUrl('C:\path\to\a\subdir\..'));
            $this->assertSame('file.png', $image->getUrl('C:\path\to\subdir\..\a'));
            $this->assertSame('file.png', $image->getUrl('C:\path\subdir\..\to\a'));
            $this->assertSame('file.png', $image->getUrl('C:\path\subdir/../to\a'));
            $this->assertSame(
                'https://example.com/images/to/a/file.png',
                $image->getUrl('C:\path', 'https://example.com/images/')
            );
        }

        $image = $this->createImage('C:\path/subdir\..\to\a/file.png');

        $this->expectException('InvalidArgumentException');

        $image->getUrl('C:\path/subdir');
    }

    public function testGetDimensions()
    {
        $imagine = $this->createMock(ImagineInterface::class);
        $imagineImage = $this->createMock(ImageInterface::class);

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

    public function testGetDimensionsFromPartialFile()
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

    public function testGetDimensionsFromPartialSvgFile()
    {
        $imagine = $this->createMock(Imagine::class);
        $imagine
            ->expects($this->never())
            ->method('open')
        ;

        if (!is_dir($this->rootDir)) {
            mkdir($this->rootDir, 0777, true);
        }

        // Only store a partial SVG file without an end tag
        file_put_contents($this->rootDir.'/dummy.svg', '<svg width="1000" height="1000">');

        $image = $this->createImage($this->rootDir.'/dummy.svg', $imagine);

        $this->assertEquals(new ImageDimensions(new Box(1000, 1000)), $image->getDimensions());
    }

    public function testGetDimensionsFromPartialSvgzFile()
    {
        $imagine = $this->createMock(Imagine::class);
        $imagine
            ->expects($this->never())
            ->method('open')
        ;

        if (!is_dir($this->rootDir)) {
            mkdir($this->rootDir, 0777, true);
        }

        // Only store a partial SVG file without an end tag
        file_put_contents($this->rootDir.'/dummy.svgz', gzencode('<svg width="1000" height="1000">'));

        $image = $this->createImage($this->rootDir.'/dummy.svgz', $imagine);

        $this->assertEquals(new ImageDimensions(new Box(1000, 1000)), $image->getDimensions());
    }

    public function testGetDimensionsInvalidSvg()
    {
        if (!is_dir($this->rootDir)) {
            mkdir($this->rootDir, 0777, true);
        }

        file_put_contents($this->rootDir.'/dummy.svg', '<nosvg width="1000" height="1000"></nosvg>');

        $imagine = $this->createMock(Imagine::class);
        $imagine
            ->method('open')
            ->willThrowException(new Exception())
        ;

        $image = $this->createImage($this->rootDir.'/dummy.svg', $imagine);

        $this->expectException('Exception');
        $image->getDimensions();
    }

    public function testGetImportantPart()
    {
        $imagine = $this->createMock(ImagineInterface::class);
        $imagineImage = $this->createMock(ImageInterface::class);

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
     * Returns an image.
     *
     * @param string           $path
     * @param ImagineInterface $imagine
     * @param Filesystem       $filesystem
     *
     * @return Image
     */
    private function createImage($path = null, $imagine = null, $filesystem = null)
    {
        if (null === $path) {
            $path = 'dummy.jpg';
        }

        if (null === $imagine) {
            $imagine = $this->createMock(ImagineInterface::class);
        }

        if (null === $filesystem) {
            $filesystem = $this->createMock(Filesystem::class);
            $filesystem
                ->method('exists')
                ->willReturn(true)
            ;
        }

        return new Image($path, $imagine, $filesystem);
    }
}
