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

use Contao\Image\Image;
use Contao\Image\ImageDimensions;
use Contao\Image\Metadata\ImageMetadata;
use Contao\Image\Metadata\MetadataParser;
use Contao\Image\PictureConfiguration;
use Contao\Image\PictureGenerator;
use Contao\Image\ResizeCalculator;
use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeCoordinates;
use Contao\Image\ResizeOptions;
use Contao\Image\Resizer;
use Contao\ImagineSvg\Imagine as SvgImagine;
use Contao\ImagineSvg\SvgBox;
use Contao\ImagineSvg\UndefinedBox;
use Imagine\Driver\InfoProvider;
use Imagine\Gd\Imagine as GdImagine;
use Imagine\Gmagick\Imagine as GmagickImagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface as ImagineImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Point;
use Imagine\Imagick\Imagine as ImagickImagine;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class ResizerTest extends TestCase
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

        (new Filesystem())->mkdir($this->rootDir);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        if (file_exists($this->rootDir)) {
            (new Filesystem())->remove($this->rootDir);
        }
    }

    public function testMetadataDifferentFormats(): void
    {
        $iptcSource = [
            '2#116' => ['Copyright 💩'],
            '2#080' => ['Creator 💩'],
            '2#115' => ['Source 💩'],
            '2#110' => ['Credit 💩'],
        ];

        $xmpExpected = [
            'http://purl.org/dc/elements/1.1/' => [
                'rights' => ['Copyright 💩'],
                'creator' => ['Creator 💩'],
            ],
            'http://ns.adobe.com/photoshop/1.0/' => [
                'Source' => ['Source 💩'],
                'Credit' => ['Credit 💩'],
            ],
        ];

        $exifExpected = [
            'IFD0' => [
                'Copyright' => 'Copyright 💩',
                'Artist' => 'Creator 💩',
            ],
        ];

        $gifExpected = [
            'Comment' => ['Copyright 💩'],
        ];

        $supportedFormat = false;

        /** @var class-string<ImagineInterface&InfoProvider> $imagineClass */
        foreach ([GdImagine::class, ImagickImagine::class, GmagickImagine::class] as $imagineClass) {
            if (!($driverInfo = $imagineClass::getDriverInfo(false)) || !$driverInfo->isFormatSupported('jpg') || !$driverInfo->isFormatSupported('gif') || !$driverInfo->isFormatSupported('webp')) {
                continue;
            }

            $imagine = new $imagineClass();

            $supportedFormat = true;
            $path = "$this->rootDir/without-metadata.jpg";
            $pathWithMeta = "$this->rootDir/with-metadata.jpg";

            $imagine->create(new Box(100, 100))->save($path);
            (new MetadataParser())->applyCopyrightToFile(
                $path,
                $pathWithMeta,
                new ImageMetadata([
                    'iptc' => $iptcSource,
                ]),
                (new ResizeOptions())->getPreserveCopyrightMetadata()
            );

            $resized = (new PictureGenerator($this->createResizer()))
                ->generate(
                    new Image($pathWithMeta, $imagine),
                    (new PictureConfiguration())
                        ->setFormats(['jpg' => ['webp', 'gif', 'jpg']]),
                    new ResizeOptions()
                )
            ;

            $this->assertExpectedArrayRecursive(
                ['xmp' => $xmpExpected, 'exif' => $exifExpected],
                (new MetadataParser())->parse($resized->getSources()[0]['src']->getPath())->getAll()
            );

            $this->assertExpectedArrayRecursive(
                ['xmp' => $xmpExpected, 'gif' => $gifExpected],
                (new MetadataParser())->parse($resized->getSources()[1]['src']->getPath())->getAll()
            );

            $this->assertExpectedArrayRecursive(
                ['xmp' => $xmpExpected, 'exif' => $exifExpected, 'iptc' => $iptcSource],
                (new MetadataParser())->parse($resized->getImg()['src']->getPath())->getAll()
            );

            (new Filesystem())->remove($path);
            (new Filesystem())->remove($pathWithMeta);
        }

        if (!$supportedFormat) {
            $this->markTestSkipped('Format jpg, gif and webp is not supported on this system by GD, Gmagick and Imagick');
        }
    }

    /**
     * @dataProvider getMetadata
     */
    public function testMetadataRoundtrip($imageFormat, ImageMetadata $metadata, array $expected): void
    {
        $supportedFormat = false;

        /** @var class-string<ImagineInterface&InfoProvider> $imagineClass */
        foreach ([GdImagine::class, ImagickImagine::class, GmagickImagine::class] as $imagineClass) {
            if (!($driverInfo = $imagineClass::getDriverInfo(false)) || !$driverInfo->isFormatSupported($imageFormat)) {
                continue;
            }

            $imagine = new $imagineClass();

            $supportedFormat = true;
            $path = "$this->rootDir/without-metadata.$imageFormat";
            $pathWithMeta = "$this->rootDir/with-metadata.$imageFormat";

            $imagine->create(new Box(100, 100))->save($path);
            (new MetadataParser())->applyCopyrightToFile(
                $path,
                $pathWithMeta,
                $metadata,
                (new ResizeOptions())->getPreserveCopyrightMetadata()
            );

            $resized = $this
                ->createResizer()
                ->resize(
                    new Image($pathWithMeta, $imagine),
                    (new ResizeConfiguration())
                        ->setWidth(50),
                    new ResizeOptions()
                )
                ->getPath()
            ;

            $this->assertExpectedArrayRecursive($expected, (new MetadataParser())->parse($pathWithMeta)->getAll());
            $this->assertExpectedArrayRecursive($expected, (new MetadataParser())->parse($resized)->getAll());

            (new Filesystem())->remove($path);
            (new Filesystem())->remove($pathWithMeta);
            (new Filesystem())->remove($resized);
        }

        if (!$supportedFormat) {
            $this->markTestSkipped("Format $imageFormat is not supported on this system by GD and Imagick");
        }
    }

    public function getMetadata(): \Generator
    {
        $sourceSingle = [
            'iptc' => [
                '2#116' => ['Copyright 💩'],
                '2#080' => ['Creator 💩'],
                '2#115' => ['Source 💩'],
                '2#110' => ['Credit 💩'],
                '2#005' => ['Title 💩'],
            ],
        ];

        $expectedSingle = [
            'iptc' => [
                '2#116' => ['Copyright 💩'],
                '2#080' => ['Creator 💩'],
                '2#115' => ['Source 💩'],
                '2#110' => ['Credit 💩'],
            ],
            'xmp' => [
                'http://purl.org/dc/elements/1.1/' => [
                    'rights' => ['Copyright 💩'],
                    'creator' => ['Creator 💩'],
                ],
                'http://ns.adobe.com/photoshop/1.0/' => [
                    'Source' => ['Source 💩'],
                    'Credit' => ['Credit 💩'],
                ],
            ],
            'exif' => [
                'IFD0' => [
                    'Copyright' => 'Copyright 💩',
                    'Artist' => 'Creator 💩',
                ],
            ],
            'gif' => [
                'Comment' => ['Copyright 💩'],
            ],
            'png' => [
                'Copyright' => ['Copyright 💩'],
                'Author' => ['Creator 💩'],
                'Source' => ['Source 💩'],
                'Disclaimer' => ['Credit 💩'],
            ],
        ];

        yield [
            'jpg',
            new ImageMetadata($sourceSingle),
            array_intersect_key($expectedSingle, ['xmp' => null, 'iptc' => null, 'exif' => null]),
        ];

        yield [
            'png',
            new ImageMetadata($sourceSingle),
            array_intersect_key($expectedSingle, ['png' => null, 'xmp' => null, 'iptc' => null, 'exif' => null]),
        ];

        yield [
            'gif',
            new ImageMetadata($sourceSingle),
            array_intersect_key($expectedSingle, ['gif' => null, 'xmp' => null]),
        ];

        yield [
            'webp',
            new ImageMetadata($sourceSingle),
            array_intersect_key($expectedSingle, ['xmp' => null, 'exif' => null]),
        ];

        $sourceFull = [
            'iptc' => [
                '2#116' => ['IPTC: Copyright 💩'],
                '2#080' => ['IPTC: Creator 💩'],
                '2#115' => ['IPTC: Source 💩'],
                '2#110' => ['IPTC: Credit 💩'],
                '2#005' => ['IPTC: Title 💩'],
            ],
            'xmp' => [
                'http://purl.org/dc/elements/1.1/' => [
                    'rights' => ['XMP: Copyright 💩'],
                    'creator' => ['XMP: Creator 💩'],
                    'title' => ['XMP: Title 💩'],
                ],
                'http://ns.adobe.com/photoshop/1.0/' => [
                    'Source' => ['XMP: Source 💩'],
                    'Credit' => ['XMP: Credit 💩'],
                ],
            ],
            'exif' => [
                'IFD0' => [
                    'Copyright' => 'EXIF: Copyright 💩',
                    'Artist' => 'EXIF: Creator 💩',
                ],
            ],
            'gif' => [
                'Comment' => ['GIF: Comment 💩'],
            ],
            'png' => [
                'Copyright' => ['PNG: Copyright 💩'],
                'Author' => ['PNG: Creator 💩'],
                'Source' => ['PNG: Source 💩'],
                'Disclaimer' => ['PNG: Credit 💩'],
                'Title' => ['PNG: Title 💩'],
            ],
        ];

        $expectedFull = $sourceFull;

        unset(
            $expectedFull['iptc']['2#005'],
            $expectedFull['xmp']['http://purl.org/dc/elements/1.1/']['title'],
            $expectedFull['png']['Title']
        );

        yield [
            'jpg',
            new ImageMetadata($sourceFull),
            array_intersect_key($expectedFull, ['xmp' => null, 'iptc' => null, 'exif' => null]),
        ];

        yield [
            'png',
            new ImageMetadata($sourceFull),
            array_intersect_key($expectedFull, ['png' => null, 'xmp' => null, 'iptc' => null, 'exif' => null]),
        ];

        yield [
            'gif',
            new ImageMetadata($sourceFull),
            array_intersect_key($expectedFull, ['gif' => null, 'xmp' => null]),
        ];

        yield [
            'webp',
            new ImageMetadata($sourceFull),
            array_intersect_key($expectedFull, ['xmp' => null, 'exif' => null]),
        ];
    }

    public function testResize(): void
    {
        $calculator = $this->createMock(ResizeCalculator::class);
        $calculator
            ->method('calculate')
            ->willReturn(new ResizeCoordinates(new Box(100, 100), new Point(0, 0), new Box(100, 100)))
        ;

        $resizer = $this->createResizer(null, $calculator);

        if (!is_dir($this->rootDir)) {
            mkdir($this->rootDir, 0777, true);
        }

        (new GdImagine())
            ->create(new Box(100, 100))
            ->save(Path::join($this->rootDir, 'dummy.jpg'))
        ;

        $image = $this->createMock(Image::class);
        $image
            ->method('getDimensions')
            ->willReturn(new ImageDimensions(new Box(200, 200)))
        ;

        $image
            ->method('getPath')
            ->willReturn(Path::join($this->rootDir, 'dummy.jpg'))
        ;

        $image
            ->method('getImagine')
            ->willReturn(new GdImagine())
        ;

        $configuration = $this->createMock(ResizeConfiguration::class);
        $defaultUmask = umask();

        try {
            foreach ([0000, 0002, 0007, 0022, 0027, 0077] as $umask) {
                umask($umask);

                $resizedImage = $resizer->resize(
                    $image,
                    $configuration,
                    (new ResizeOptions())
                        ->setImagineOptions([
                            'jpeg_quality' => 95,
                            'interlace' => ImagineImageInterface::INTERLACE_PLANE,
                            'jpeg_sampling_factors' => [2, 1, 1],
                        ])
                );

                $this->assertEquals(new ImageDimensions(new Box(100, 100)), $resizedImage->getDimensions());
                $this->assertMatchesRegularExpression('(/[0-9a-f]/dummy-[0-9a-f]{8}.jpg$)', $resizedImage->getPath());
                $this->assertFilePermissions(0666, $resizedImage->getPath());

                unlink($resizedImage->getPath());
            }
        } finally {
            umask($defaultUmask);
        }

        $resizedImage = $resizer->resize(
            $image,
            $configuration,
            (new ResizeOptions())->setTargetPath(Path::join($this->rootDir, 'target-path.jpg'))
        );

        $this->assertEquals(new ImageDimensions(new Box(100, 100)), $resizedImage->getDimensions());
        $this->assertSame(Path::join($this->rootDir, 'target-path.jpg'), $resizedImage->getPath());
        $this->assertFilePermissions(0666, $resizedImage->getPath());

        // Replace target image with larger image
        (new GdImagine())
            ->create(new Box(200, 200))
            ->save(Path::join($this->rootDir, 'target-path.jpg'))
        ;

        // Resize with override
        $resizedImage = $resizer->resize(
            $image,
            $configuration,
            (new ResizeOptions())->setTargetPath(Path::join($this->rootDir, 'target-path.jpg'))
        );

        $this->assertSame(Path::join($this->rootDir, 'target-path.jpg'), $resizedImage->getPath());
        $this->assertEquals(new ImageDimensions(new Box(100, 100)), $resizedImage->getDimensions());
        $this->assertFilePermissions(0666, $resizedImage->getPath());
    }

    public function testResizeSvg(): void
    {
        $xml = '<?xml version="1.0"?>'
            .'<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="100" height="100"></svg>';

        if (!is_dir($this->rootDir)) {
            mkdir($this->rootDir, 0777, true);
        }

        file_put_contents(Path::join($this->rootDir, 'dummy.svg'), $xml);

        $calculator = $this->createMock(ResizeCalculator::class);
        $calculator
            ->method('calculate')
            ->willReturn(new ResizeCoordinates(new Box(100, 100), new Point(0, 0), new Box(100, 100)))
        ;

        $resizer = $this->createResizer(null, $calculator);

        $image = $this->createMock(Image::class);
        $image
            ->method('getDimensions')
            ->willReturn(new ImageDimensions(new Box(200, 200)))
        ;

        $image
            ->method('getPath')
            ->willReturn(Path::join($this->rootDir, 'dummy.svg'))
        ;

        $image
            ->method('getImagine')
            ->willReturn(new SvgImagine())
        ;

        $configuration = $this->createMock(ResizeConfiguration::class);

        $resizedImage = $resizer->resize(
            $image,
            $configuration,
            (new ResizeOptions())
                ->setImagineOptions([
                    'jpeg_quality' => 95,
                    'interlace' => ImagineImageInterface::INTERLACE_PLANE,
                    'jpeg_sampling_factors' => [2, 1, 1],
                ])
        );

        $this->assertSame(100, $resizedImage->getDimensions()->getSize()->getWidth());
        $this->assertSame(100, $resizedImage->getDimensions()->getSize()->getHeight());
        $this->assertFalse($resizedImage->getDimensions()->isRelative());
        $this->assertFalse($resizedImage->getDimensions()->isUndefined());
        $this->assertMatchesRegularExpression('(/[0-9a-f]/dummy-[0-9a-f]{8}.svg$)', $resizedImage->getPath());
        $this->assertFilePermissions(0666, $resizedImage->getPath());

        unlink($resizedImage->getPath());

        $resizedImage = $resizer->resize(
            $image,
            $configuration,
            (new ResizeOptions())->setTargetPath(Path::join($this->rootDir, 'target-path.svg'))
        );

        $this->assertSame(100, $resizedImage->getDimensions()->getSize()->getWidth());
        $this->assertSame(100, $resizedImage->getDimensions()->getSize()->getHeight());
        $this->assertFalse($resizedImage->getDimensions()->isRelative());
        $this->assertFalse($resizedImage->getDimensions()->isUndefined());
        $this->assertSame(Path::join($this->rootDir, 'target-path.svg'), $resizedImage->getPath());
        $this->assertFilePermissions(0666, $resizedImage->getPath());

        unlink($resizedImage->getPath());
    }

    public function testResizeCache(): void
    {
        $calculator = $this->createMock(ResizeCalculator::class);
        $calculator
            ->method('calculate')
            ->willReturn(new ResizeCoordinates(new Box(100, 100), new Point(0, 0), new Box(100, 100)))
        ;

        $resizer = $this->createResizer(null, $calculator);

        if (!is_dir($this->rootDir)) {
            mkdir($this->rootDir, 0777, true);
        }

        (new GdImagine())
            ->create(new Box(100, 100))
            ->save(Path::join($this->rootDir, 'dummy.jpg'))
        ;

        $image = $this->createMock(Image::class);
        $image
            ->method('getDimensions')
            ->willReturn(new ImageDimensions(new Box(200, 200)))
        ;

        $image
            ->method('getPath')
            ->willReturn(Path::join($this->rootDir, 'dummy.jpg'))
        ;

        $image
            ->method('getImagine')
            ->willReturn(new GdImagine())
        ;

        $configuration = $this->createMock(ResizeConfiguration::class);
        $resizedImage = $resizer->resize($image, $configuration, new ResizeOptions());

        $this->assertEquals(new ImageDimensions(new Box(100, 100)), $resizedImage->getDimensions());
        $this->assertMatchesRegularExpression('(/[0-9a-f]/dummy-[0-9a-f]{8}.jpg$)', $resizedImage->getPath());
        $this->assertFilePermissions(0666, $resizedImage->getPath());

        $imagePath = $resizedImage->getPath();

        // Different cache file for testing
        (new GdImagine())
            ->create(new Box(200, 100))
            ->save($imagePath)
        ;

        // With cache
        $resizedImage = $resizer->resize($image, $configuration, new ResizeOptions());

        $this->assertSame($imagePath, $resizedImage->getPath());
        $this->assertSame(200, getimagesize($imagePath)[0], 'Cache file should no be overwritten');

        // With cache and target path
        $targetPath = Path::join($this->rootDir, 'target-image.jpg');
        $resizedImage = $resizer->resize($image, $configuration, (new ResizeOptions())->setTargetPath($targetPath));

        $this->assertSame($targetPath, $resizedImage->getPath());
        $this->assertFileEquals($imagePath, $targetPath, 'Cache file should have been copied');

        // With different imagine options
        $resizedImage = $resizer->resize(
            $image,
            $configuration,
            (new ResizeOptions())->setImagineOptions(['jpeg_quality' => 10])
        );

        $this->assertNotSame($imagePath, $resizedImage->getPath());
        $this->assertSame(100, getimagesize($resizedImage->getPath())[0], 'New cache file should have been created');

        // With different paths, but same relative path
        $subDir = Path::join($this->rootDir, 'sub/dir');

        mkdir($subDir, 0777, true);
        copy(Path::join($this->rootDir, 'dummy.jpg'), Path::join($subDir, 'dummy.jpg'));
        touch(Path::join($subDir, 'dummy.jpg'), filemtime(Path::join($this->rootDir, 'dummy.jpg')));

        $subResizer = $this->createResizer($subDir, $calculator);

        $subImage = $this->createMock(Image::class);
        $subImage
            ->method('getDimensions')
            ->willReturn(new ImageDimensions(new Box(200, 200)))
        ;

        $subImage
            ->method('getPath')
            ->willReturn(Path::join($subDir, 'dummy.jpg'))
        ;

        $subImage
            ->method('getImagine')
            ->willReturn(new GdImagine())
        ;

        $resizedImage = $subResizer->resize($subImage, $configuration, (new ResizeOptions())->setBypassCache(true));

        $this->assertSame(
            substr($imagePath, \strlen($this->rootDir)),
            substr($resizedImage->getPath(), \strlen($subDir)),
            'The hash should be the same if the image path relative to the cacheDir is the same'
        );

        // Without cache
        $resizedImage = $resizer->resize($image, $configuration, (new ResizeOptions())->setBypassCache(true));

        $this->assertSame($imagePath, $resizedImage->getPath());
        $this->assertSame(100, getimagesize($imagePath)[0]);
    }

    public function testResizeUndefinedSize(): void
    {
        $imagePath = Path::join($this->rootDir, 'dummy.jpg');
        $resizer = $this->createResizer();

        if (!is_dir($this->rootDir)) {
            mkdir($this->rootDir, 0777, true);
        }

        file_put_contents($imagePath, '');

        $image = $this->createMock(Image::class);
        $image
            ->method('getDimensions')
            ->willReturn(new ImageDimensions(
                class_exists(SvgBox::class) ? SvgBox::createTypeNone() : new UndefinedBox()
            ))
        ;

        $image
            ->method('getPath')
            ->willReturn($imagePath)
        ;

        $image
            ->method('getImagine')
            ->willReturn(new GdImagine())
        ;

        $configuration = $this->createMock(ResizeConfiguration::class);
        $resizedImage = $resizer->resize(
            $image,
            $configuration,
            (new ResizeOptions())->setSkipIfDimensionsMatch(true)
        );

        $this->assertSame($imagePath, $resizedImage->getPath());

        $resizedImage = $resizer->resize($image, $configuration, new ResizeOptions());

        $this->assertSame($imagePath, $resizedImage->getPath());
    }

    public function testResizeEmptyConfig(): void
    {
        $imagePath = Path::join($this->rootDir, 'dummy.jpg');
        $resizer = $this->createResizer();

        if (!is_dir($this->rootDir)) {
            mkdir($this->rootDir, 0777, true);
        }

        (new GdImagine())
            ->create(new Box(100, 100))
            ->save($imagePath)
        ;

        /** @var Image|MockObject $image */
        $image = $this->createMock(Image::class);
        $image
            ->method('getDimensions')
            ->willReturn(new ImageDimensions(new Box(100, 100)))
        ;

        $image
            ->method('getPath')
            ->willReturn($imagePath)
        ;

        $image
            ->method('getImagine')
            ->willReturn(new GdImagine())
        ;

        $configuration = $this->createMock(ResizeConfiguration::class);
        $configuration
            ->method('isEmpty')
            ->willReturn(true)
        ;

        $resizedImage = $resizer->resize($image, $configuration, new ResizeOptions());

        $this->assertMatchesRegularExpression('(/[0-9a-f]/dummy-[0-9a-f]{8}.jpg$)', $resizedImage->getPath());
        $this->assertNotSame($image, $resizedImage);
    }

    public function testResizeEmptyConfigSkipsMatchingDimensions(): void
    {
        $imagePath = Path::join($this->rootDir, 'dummy.jpg');
        $resizer = $this->createResizer();

        if (!is_dir($this->rootDir)) {
            mkdir($this->rootDir, 0777, true);
        }

        file_put_contents($imagePath, '');

        /** @var Image|MockObject $image */
        $image = $this->createMock(Image::class);
        $image
            ->method('getDimensions')
            ->willReturn(new ImageDimensions(new Box(100, 100)))
        ;

        $image
            ->method('getPath')
            ->willReturn($imagePath)
        ;

        $image
            ->method('getImagine')
            ->willReturn(new GdImagine())
        ;

        $configuration = $this->createMock(ResizeConfiguration::class);
        $configuration
            ->method('isEmpty')
            ->willReturn(true)
        ;

        $resizedImage = $resizer->resize(
            $image,
            $configuration,
            (new ResizeOptions())->setSkipIfDimensionsMatch(true)
        );

        $this->assertSame($image->getPath(), $resizedImage->getPath());
        $this->assertNotSame($image, $resizedImage);
    }

    public function testResizeEmptyConfigWithFormat(): void
    {
        $imagePath = Path::join($this->rootDir, 'dummy.jpg');
        $resizer = $this->createResizer();

        if (!is_dir($this->rootDir)) {
            mkdir($this->rootDir, 0777, true);
        }

        (new GdImagine())
            ->create(new Box(100, 100))
            ->save($imagePath)
        ;

        /** @var Image|MockObject $image */
        $image = $this->createMock(Image::class);
        $image
            ->method('getDimensions')
            ->willReturn(new ImageDimensions(new Box(100, 100)))
        ;

        $image
            ->method('getPath')
            ->willReturn($imagePath)
        ;

        $image
            ->method('getImagine')
            ->willReturn(new GdImagine())
        ;

        $configuration = $this->createMock(ResizeConfiguration::class);
        $configuration
            ->method('isEmpty')
            ->willReturn(true)
        ;

        $resizedImage = $resizer->resize(
            $image,
            $configuration,
            (new ResizeOptions())
                ->setSkipIfDimensionsMatch(true)
                ->setImagineOptions(['format' => 'png'])
        );

        $this->assertMatchesRegularExpression('(/[0-9a-f]/dummy-[0-9a-f]{8}.png$)', $resizedImage->getPath());
        $this->assertNotSame($image, $resizedImage);
    }

    public function testResizeSameDimensions(): void
    {
        $path = Path::join($this->rootDir, 'dummy.jpg');

        $calculator = $this->createMock(ResizeCalculator::class);
        $calculator
            ->method('calculate')
            ->willReturn(new ResizeCoordinates(new Box(100, 100), new Point(0, 0), new Box(100, 100)))
        ;

        $resizer = $this->createResizer(null, $calculator);

        if (!is_dir($this->rootDir)) {
            mkdir($this->rootDir, 0777, true);
        }

        (new GdImagine())
            ->create(new Box(100, 100))
            ->save($path)
        ;

        $image = $this->createMock(Image::class);
        $image
            ->method('getDimensions')
            ->willReturn(new ImageDimensions(new Box(100, 100)))
        ;

        $image
            ->method('getPath')
            ->willReturn($path)
        ;

        $image
            ->method('getImagine')
            ->willReturn(new GdImagine())
        ;

        $configuration = $this->createMock(ResizeConfiguration::class);
        $resizedImage = $resizer->resize(
            $image,
            $configuration,
            (new ResizeOptions())->setSkipIfDimensionsMatch(true)
        );

        $this->assertEquals(new ImageDimensions(new Box(100, 100)), $resizedImage->getDimensions());
        $this->assertSame($path, $resizedImage->getPath());

        $resizedImage = $resizer->resize(
            $image,
            $configuration,
            (new ResizeOptions())
                ->setTargetPath(Path::join($this->rootDir, 'target-path.jpg'))
                ->setSkipIfDimensionsMatch(true)
        );

        $this->assertEquals(new ImageDimensions(new Box(100, 100)), $resizedImage->getDimensions());
        $this->assertSame(Path::join($this->rootDir, 'target-path.jpg'), $resizedImage->getPath());
    }

    public function testResizeSameDimensionsRelative(): void
    {
        $xml = '<?xml version="1.0"?>'.
            '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 100 100"></svg>';

        if (!is_dir($this->rootDir)) {
            mkdir($this->rootDir, 0777, true);
        }

        file_put_contents(Path::join($this->rootDir, 'dummy.svg'), $xml);

        $calculator = $this->createMock(ResizeCalculator::class);
        $calculator
            ->method('calculate')
            ->willReturn(new ResizeCoordinates(new Box(100, 100), new Point(0, 0), new Box(100, 100)))
        ;

        $resizer = $this->createResizer(null, $calculator);

        $image = $this->createMock(Image::class);
        $image
            ->method('getDimensions')
            ->willReturn(new ImageDimensions(new Box(100, 100), true))
        ;

        $image
            ->method('getPath')
            ->willReturn(Path::join($this->rootDir, 'dummy.svg'))
        ;

        $image
            ->method('getImagine')
            ->willReturn(new SvgImagine())
        ;

        $configuration = $this->createMock(ResizeConfiguration::class);
        $resizedImage = $resizer->resize($image, $configuration, new ResizeOptions());

        $this->assertSame(100, $resizedImage->getDimensions()->getSize()->getWidth());
        $this->assertSame(100, $resizedImage->getDimensions()->getSize()->getHeight());
        $this->assertFalse($resizedImage->getDimensions()->isRelative());
        $this->assertMatchesRegularExpression('(/[0-9a-f]/dummy-[0-9a-f]{8}.svg$)', $resizedImage->getPath());

        unlink($resizedImage->getPath());
    }

    public function testResizeEmptyConfigRotatedImage(): void
    {
        $imagePath = Path::join($this->rootDir, 'dummy.jpg');
        $resizer = $this->createResizer();

        if (!is_dir($this->rootDir)) {
            mkdir($this->rootDir, 0777, true);
        }

        (new GdImagine())
            ->create(new Box(100, 100))
            ->save($imagePath)
        ;

        /** @var Image|MockObject $image */
        $image = $this->createMock(Image::class);
        $image
            ->method('getDimensions')
            ->willReturn(new ImageDimensions(new Box(100, 100), null, null, ImageDimensions::ORIENTATION_180))
        ;

        $image
            ->method('getPath')
            ->willReturn($imagePath)
        ;

        $image
            ->method('getImagine')
            ->willReturn(new GdImagine())
        ;

        $configuration = $this->createMock(ResizeConfiguration::class);
        $configuration
            ->method('isEmpty')
            ->willReturn(true)
        ;

        $resizedImage = $resizer->resize($image, $configuration, new ResizeOptions());

        $this->assertMatchesRegularExpression('(/[0-9a-f]/dummy-[0-9a-f]{8}.jpg$)', $resizedImage->getPath());
        $this->assertNotSame($image, $resizedImage);
    }

    public function testResizeEmptyConfigNoSkip(): void
    {
        $imagePath = Path::join($this->rootDir, 'dummy.jpg');
        $resizer = $this->createResizer();

        if (!is_dir($this->rootDir)) {
            mkdir($this->rootDir, 0777, true);
        }

        (new GdImagine())
            ->create(new Box(100, 100))
            ->save($imagePath)
        ;

        /** @var Image|MockObject $image */
        $image = $this->createMock(Image::class);
        $image
            ->method('getDimensions')
            ->willReturn(new ImageDimensions(new Box(100, 100)))
        ;

        $image
            ->method('getPath')
            ->willReturn($imagePath)
        ;

        $image
            ->method('getImagine')
            ->willReturn(new GdImagine())
        ;

        $configuration = $this->createMock(ResizeConfiguration::class);
        $configuration
            ->method('isEmpty')
            ->willReturn(true)
        ;

        $resizedImage = $resizer->resize($image, $configuration, new ResizeOptions());

        $this->assertMatchesRegularExpression('(/[0-9a-f]/dummy-[0-9a-f]{8}.jpg$)', $resizedImage->getPath());
        $this->assertNotSame($image, $resizedImage);
    }

    public static function assertMatchesRegularExpression(string $pattern, string $string, string $message = ''): void
    {
        if (method_exists(parent::class, 'assertMatchesRegularExpression')) {
            parent::assertMatchesRegularExpression($pattern, $string, $message);
        } else {
            parent::assertRegExp($pattern, $string, $message);
        }
    }

    private function assertExpectedArrayRecursive(array $expected, array $actual): void
    {
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $actual);

            if (\is_array($value) && !array_is_list($value)) {
                $this->assertIsArray($actual[$key]);
                $this->assertExpectedArrayRecursive($value, $actual[$key]);

                continue;
            }

            $this->assertSame($value, $actual[$key]);
        }
    }

    private function createResizer(string $cacheDir = null, ResizeCalculator $calculator = null, Filesystem $filesystem = null): Resizer
    {
        if (null === $cacheDir) {
            $cacheDir = $this->rootDir;
        }

        return new Resizer($cacheDir, $calculator, $filesystem);
    }

    private function assertFilePermissions(int $expectedPermissions, string $path): void
    {
        $this->assertSame(
            sprintf('%o', $expectedPermissions & ~umask() & 0777),
            sprintf('%o', fileperms($path) & 0777)
        );
    }
}
