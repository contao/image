<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image\Tests\Metadata;

use Contao\Image\Metadata\ExifFormat;
use Contao\Image\Metadata\GifFormat;
use Contao\Image\Metadata\ImageMetadata;
use Contao\Image\Metadata\IptcFormat;
use Contao\Image\Metadata\MetadataReaderWriter;
use Contao\Image\Metadata\PngFormat;
use Contao\Image\Metadata\XmpFormat;
use Contao\Image\ResizeOptions;
use Imagine\Gd\Imagine as GdImagine;
use Imagine\Gmagick\Imagine as GmagickImagine;
use Imagine\Image\Box;
use Imagine\Imagick\Imagine as ImagickImagine;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class MetadataReaderWriterTest extends TestCase
{
    /**
     * @dataProvider getParse
     */
    public function testParse(string $sourcePath, array $expected, array $expectedReadable): void
    {
        $this->assertExpectedArrayRecursive(
            $expected,
            (new MetadataReaderWriter())->parse($sourcePath)->getAll()
        );

        $this->assertExpectedArrayRecursive(
            $expectedReadable,
            (new MetadataReaderWriter())->toReadable((new MetadataReaderWriter())->parse($sourcePath))
        );
    }

    public function getParse(): \Generator
    {
        $paths = [
            'jxl1.jxl' => [
                'url' => 'https://github.com/Exiv2/exiv2/raw/a7a9835/test/data/Reagan.jxl',
                'meta' => [
                    'exif' => ['IFD0' => ['Artist' => 'Photographerís Mate 3rd Class (A']],
                    'xmp' => ['http://purl.org/dc/elements/1.1/' => ['creator' => ['Photographerís Mate 3rd Class (A']]],
                ],
                'readable' => [
                    'exif' => ['Artist' => ['Photographerís Mate 3rd Class (A']],
                    'xmp' => ['dc:creator' => ['Photographerís Mate 3rd Class (A']],
                ],
            ],
            'jxl2.jxl' => [
                'url' => 'https://github.com/Exiv2/exiv2/raw/a7a9835/test/data/issue_2233_poc1.jxl',
                'meta' => [
                    'exif' => ['IFD0' => ['Software' => 'GIMP 2.99.11']],
                    'xmp' => ['http://purl.org/dc/elements/1.1/' => ['creator' => ['exiv2.org']]],
                ],
                'readable' => [
                    'exif' => ['Software' => ['GIMP 2.99.11']],
                    'xmp' => ['dc:creator' => ['exiv2.org']],
                ],
            ],
            'jxl3.jxl' => [
                'url' => 'https://github.com/Exiv2/exiv2/raw/a7a9835/test/data/issue_2233_poc2.jxl',
                'meta' => [
                    'exif' => ['IFD0' => ['Software' => 'GIMP 2.99.11']],
                    'xmp' => ['http://purl.org/dc/elements/1.1/' => ['creator' => ['exiv2.org']]],
                ],
                'readable' => [
                    'exif' => ['Software' => ['GIMP 2.99.11']],
                    'xmp' => ['dc:creator' => ['exiv2.org']],
                ],
            ],
            'avif1.avif' => [
                'url' => 'https://github.com/Exiv2/exiv2/raw/a7a9835/test/data/avif_exif_xmp.avif',
                'meta' => [
                    'exif' => ['IFD0' => ['Software' => 'GIMP 2.99.5']],
                    'xmp' => ['http://purl.org/dc/elements/1.1/' => ['creator' => ['type="Seq" Developer']]],
                ],
                'readable' => [
                    'exif' => ['Software' => ['GIMP 2.99.5']],
                    'xmp' => ['dc:creator' => ['type="Seq" Developer']],
                ],
            ],
            'avif2.avif' => [
                'url' => 'https://github.com/Exiv2/exiv2/raw/a7a9835/test/data/avif_metadata2.avif',
                'meta' => [
                    'exif' => ['IFD0' => ['Software' => 'GIMP 2.99.5']],
                    'xmp' => ['http://purl.org/dc/elements/1.1/' => ['creator' => ['type="Seq" me']]],
                ],
                'readable' => [
                    'exif' => ['Software' => ['GIMP 2.99.5']],
                    'xmp' => ['dc:creator' => ['type="Seq" me']],
                ],
            ],
            'heic1.heic' => [
                'url' => 'https://github.com/Exiv2/exiv2/raw/a7a9835/test/data/IMG_3578.heic',
                'meta' => [
                    'exif' => ['IFD0' => ['Software' => '13.4.1']],
                ],
                'readable' => [
                    'exif' => ['Software' => ['13.4.1']],
                ],
            ],
            'heic2.heic' => [
                'url' => 'https://github.com/Exiv2/exiv2/raw/a7a9835/test/data/Stonehenge.heic',
                'meta' => [
                    'exif' => ['IFD0' => ['Software' => 'Ver.1.00 ']],
                    'xmp' => ['http://purl.org/dc/elements/1.1/' => ['description' => ['Classic View']]],
                ],
                'readable' => [
                    'exif' => ['Software' => ['Ver.1.00']],
                    'xmp' => ['dc:description' => ['Classic View']],
                ],
            ],
            'png.png' => [
                'url' => 'https://github.com/Exiv2/exiv2/raw/a7a9835/test/data/imagemagick.png',
                'meta' => [
                    'png' => ['Software' => ['digiKam 0.9.0-svn ( libpng version 1.2.8 - December 3, 2004 (header) )']],
                    'exif' => ['IFD0' => ['Software' => 'digiKam-0.9.0-svn']],
                    'iptc' => ['2#110' => ['Spielberg']],
                ],
                'readable' => [
                    'png' => ['Software' => ['digiKam 0.9.0-svn ( libpng version 1.2.8 - December 3, 2004 (header) )']],
                    'exif' => ['Software' => ['digiKam-0.9.0-svn']],
                    'iptc' => ['Credit' => ['Spielberg']],
                ],
            ],
        ];

        foreach ($paths as $filename => $data) {
            $path = __DIR__.'/../fixtures/'.$filename;

            if (!(new Filesystem())->exists($path)) {
                (new Filesystem())->copy($data['url'], $path);
            }

            yield [$path, $data['meta'], $data['readable']];
        }
    }

    /**
     * @dataProvider getFormats
     */
    public function testApplyDoesNotChangeForEmptyData(string $format): void
    {
        $supported = false;

        foreach ([GdImagine::class, ImagickImagine::class, GmagickImagine::class] as $imagineClass) {
            if (!($driverInfo = $imagineClass::getDriverInfo(false)) || !$driverInfo->isFormatSupported($format)) {
                continue;
            }

            $supported = true;
            $sourcePath = (new Filesystem())->tempnam(sys_get_temp_dir(), 'img');
            $targetPath = (new Filesystem())->tempnam(sys_get_temp_dir(), 'img');
            $imagine = new $imagineClass();

            $imagine->create(new Box(100, 100))->save($sourcePath, ['format' => $format]);

            (new MetadataReaderWriter())->applyCopyrightToFile(
                $sourcePath,
                $targetPath,
                new ImageMetadata([
                    XmpFormat::NAME => [],
                    IptcFormat::NAME => [],
                    ExifFormat::NAME => [],
                    PngFormat::NAME => [],
                    GifFormat::NAME => [],
                ]),
                (new ResizeOptions())->getPreserveCopyrightMetadata()
            );

            $this->assertFileEquals($sourcePath, $targetPath);

            (new Filesystem())->remove([$sourcePath, $targetPath]);
        }

        if (!$supported) {
            $this->markTestSkipped(sprintf('Format "%s" not supported on this system', $format));
        }
    }

    public function getFormats(): \Generator
    {
        yield ['png', 'gif', 'jpg', 'webp'];
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
}
