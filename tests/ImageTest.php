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

use Contao\Image\Exception\FileNotExistsException;
use Contao\Image\Exception\InvalidArgumentException;
use Contao\Image\Image;
use Contao\Image\ImageDimensions;
use Contao\Image\ImportantPart;
use Contao\ImagineSvg\Imagine;
use Imagine\Gd\Imagine as GdImagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Metadata\MetadataBag;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class ImageTest extends TestCase
{
    /**
     * @var string
     */
    private $rootDir;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->rootDir = Path::canonicalize(__DIR__.'/tmp');
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        if ((new Filesystem())->exists($this->rootDir)) {
            (new Filesystem())->remove($this->rootDir);
        }
    }

    public function testConstructorNonExistentPath(): void
    {
        $this->expectException(FileNotExistsException::class);

        new Image('/path/to/non/existent/file.jpg', $this->createMock(ImagineInterface::class));
    }

    public function testConstructorDirPath(): void
    {
        $this->expectException(FileNotExistsException::class);
        $this->createImage(__DIR__);
    }

    public function testInstantiationMissingFiles(): void
    {
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem
            ->method('exists')
            ->willReturn(false)
        ;

        $this->expectException(FileNotExistsException::class);
        $this->createImage(null, null, $filesystem);
    }

    public function testGetPath(): void
    {
        $image = $this->createImage('/path/filename.jpeg');

        $this->assertSame('/path/filename.jpeg', $image->getPath());
    }

    public function testGetUrl(): void
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

        $this->expectException(InvalidArgumentException::class);

        $image->getUrl('/path/t');
    }

    public function testGetUrlRealtivePath(): void
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

        $this->expectException(InvalidArgumentException::class);

        $image->getUrl('C:\path/subdir');
    }

    public function testGetDimensions(): void
    {
        $imagineImage = $this->createMock(ImageInterface::class);
        $imagineImage
            ->method('getSize')
            ->willReturn(new Box(100, 100))
        ;

        $imagineImage
            ->method('metadata')
            ->willReturn(new MetadataBag())
        ;

        $imagine = $this->createMock(ImagineInterface::class);
        $imagine
            ->method('open')
            ->willReturn($imagineImage)
        ;

        $image = $this->createImage(null, $imagine);

        $this->assertEquals(new ImageDimensions(new Box(100, 100)), $image->getDimensions());
    }

    /**
     * @dataProvider getDimensionsFromExifRotated
     */
    public function testGetDimensionsFromExifRotatedFile(int $orientation, int $width, int $height, int $expectedWidth, int $expectedHeight, int $expectedOrientation = null): void
    {
        if (!\function_exists('exif_read_data')) {
            $this->markTestSkipped('The PHP EXIF extension is not installed');
        }

        if (!is_dir($this->rootDir)) {
            (new Filesystem())->mkdir($this->rootDir);
        }

        $image = (new GdImagine())
            ->create(new Box($width, $height))
            ->get('jpg')
        ;

        (new Filesystem())->dumpFile(Path::join($this->rootDir, 'dummy.jpg'), $this->addImageOrientation($image, $orientation));

        $image = $this->createImage(Path::join($this->rootDir, 'dummy.jpg'));

        $dimensions = $image->getDimensions();

        $this->assertSame($expectedWidth, $dimensions->getSize()->getWidth());
        $this->assertSame($expectedHeight, $dimensions->getSize()->getHeight());
        $this->assertSame($expectedOrientation ?? $orientation, $dimensions->getOrientation());
    }

    /**
     * @dataProvider getDimensionsFromExifRotated
     */
    public function testGetDimensionsFromExifRotatedImage(int $orientation, int $width, int $height, int $expectedWidth, int $expectedHeight, int $expectedOrientation = null): void
    {
        $imagineImage = $this->createMock(ImageInterface::class);
        $imagineImage
            ->method('getSize')
            ->willReturn(new Box($width, $height))
        ;

        $imagineImage
            ->method('metadata')
            ->willReturn(new MetadataBag(['ifd0.Orientation' => $orientation]))
        ;

        $imagine = $this->createMock(ImagineInterface::class);
        $imagine
            ->method('open')
            ->willReturn($imagineImage)
        ;

        $image = $this->createImage(null, $imagine);

        $dimensions = $image->getDimensions();

        $this->assertSame($expectedWidth, $dimensions->getSize()->getWidth());
        $this->assertSame($expectedHeight, $dimensions->getSize()->getHeight());
        $this->assertSame($expectedOrientation ?? $orientation, $dimensions->getOrientation());
    }

    public function getDimensionsFromExifRotated(): \Generator
    {
        yield [ImageDimensions::ORIENTATION_NORMAL, 22, 11, 22, 11];
        yield [ImageDimensions::ORIENTATION_90, 22, 11, 11, 22];
        yield [ImageDimensions::ORIENTATION_180, 22, 11, 22, 11];
        yield [ImageDimensions::ORIENTATION_270, 22, 11, 11, 22];
        yield [ImageDimensions::ORIENTATION_MIRROR, 22, 11, 22, 11];
        yield [ImageDimensions::ORIENTATION_MIRROR_90, 22, 11, 11, 22];
        yield [ImageDimensions::ORIENTATION_MIRROR_180, 22, 11, 22, 11];
        yield [ImageDimensions::ORIENTATION_MIRROR_270, 22, 11, 11, 22];
        yield [0, 22, 11, 22, 11, ImageDimensions::ORIENTATION_NORMAL];
        yield [9, 22, 11, 22, 11, ImageDimensions::ORIENTATION_NORMAL];
        yield [255, 22, 11, 22, 11, ImageDimensions::ORIENTATION_NORMAL];
    }

    public function testGetDimensionsFromPartialFile(): void
    {
        if (!is_dir($this->rootDir)) {
            (new Filesystem())->mkdir($this->rootDir);
        }

        $image = (new GdImagine())
            ->create(new Box(1000, 1000))
            ->get('jpg')
        ;

        // Only store the first 500 bytes of the image
        (new Filesystem())->dumpFile(Path::join($this->rootDir, 'dummy.jpg'), substr($image, 0, 500));

        $image = $this->createImage(Path::join($this->rootDir, 'dummy.jpg'));

        $this->assertEquals(new ImageDimensions(new Box(1000, 1000)), $image->getDimensions());
    }

    public function testGetDimensionsFromPartialSvgFile(): void
    {
        $imagine = $this->createMock(Imagine::class);
        $imagine
            ->expects($this->never())
            ->method('open')
        ;

        if (!is_dir($this->rootDir)) {
            (new Filesystem())->mkdir($this->rootDir);
        }

        // Only store a partial SVG file without an end tag
        (new Filesystem())->dumpFile(Path::join($this->rootDir, 'dummy.svg'), '<svg width="1000" height="1000">');

        $image = $this->createImage(Path::join($this->rootDir, 'dummy.svg'), $imagine);

        $this->assertSame(1000, $image->getDimensions()->getSize()->getWidth());
        $this->assertSame(1000, $image->getDimensions()->getSize()->getHeight());
        $this->assertFalse($image->getDimensions()->isRelative());
        $this->assertFalse($image->getDimensions()->isUndefined());
    }

    public function testGetDimensionsFromPartialSvgzFile(): void
    {
        $imagine = $this->createMock(Imagine::class);
        $imagine
            ->expects($this->never())
            ->method('open')
        ;

        if (!is_dir($this->rootDir)) {
            (new Filesystem())->mkdir($this->rootDir);
        }

        // Only store a partial SVG file without an end tag
        (new Filesystem())->dumpFile(Path::join($this->rootDir, 'dummy.svgz'), gzencode('<svg width="1000" height="1000">'));

        $image = $this->createImage(Path::join($this->rootDir, 'dummy.svgz'), $imagine);

        $this->assertSame(1000, $image->getDimensions()->getSize()->getWidth());
        $this->assertSame(1000, $image->getDimensions()->getSize()->getHeight());
        $this->assertFalse($image->getDimensions()->isRelative());
        $this->assertFalse($image->getDimensions()->isUndefined());
    }

    public function testGetDimensionsInvalidSvg(): void
    {
        if (!is_dir($this->rootDir)) {
            (new Filesystem())->mkdir($this->rootDir);
        }

        (new Filesystem())->dumpFile(Path::join($this->rootDir, 'dummy.svg'), '<nosvg width="1000" height="1000"></nosvg>');

        $imagine = $this->createMock(Imagine::class);
        $imagine
            ->method('open')
            ->willThrowException(new \Exception())
        ;

        $image = $this->createImage(Path::join($this->rootDir, 'dummy.svg'), $imagine);

        $this->expectException('Exception');
        $image->getDimensions();
    }

    public function testGetImportantPart(): void
    {
        $imagineImage = $this->createMock(ImageInterface::class);

        $imagine = $this->createMock(ImagineInterface::class);
        $imagine
            ->method('open')
            ->willReturn($imagineImage)
        ;

        $imagineImage
            ->method('getSize')
            ->willReturn(new Box(100, 100))
        ;

        $image = $this->createImage(null, $imagine);

        $this->assertEquals(new ImportantPart(), $image->getImportantPart());

        $image->setImportantPart(new ImportantPart(0.1, 0.1, 0.8, 0.8));

        $this->assertEquals(new ImportantPart(0.1, 0.1, 0.8, 0.8), $image->getImportantPart());
    }

    /**
     * @param ImagineInterface&MockObject $imagine
     * @param Filesystem&MockObject       $filesystem
     */
    private function createImage(string $path = null, ImagineInterface $imagine = null, Filesystem $filesystem = null): Image
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

    /**
     * Insert an EXIF header into the passed JPEG data with the specified orientation.
     */
    private function addImageOrientation(string $jpegData, int $orientation): string
    {
        $exif = implode('', [
            "\x45\x78\x69\x66\x00\x00", // Exif header
            "\x49\x49\x2a\x00\x08\x00\x00\x00", // TIFF header
            "\x01\x00", // IFD0 entries
            "\x12\x01\x03\x00\x01\x00\x00\x00".\chr($orientation)."\x00\x12\x00", // IFD0-00 Orientation
            "\x00\x00\x00\x00", // Next IFD
        ]);

        $length = \strlen($exif) + 2;
        $exif = "\xFF\xE1".\chr(($length >> 8) & 0xFF).\chr($length & 0xFF).$exif;

        return substr($jpegData, 0, 2).$exif.substr($jpegData, 2);
    }
}
