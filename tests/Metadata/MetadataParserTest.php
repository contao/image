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

use Contao\Image\Metadata\MetadataParser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class MetadataParserTest extends TestCase
{
    /**
     * @dataProvider getParse
     */
    public function testParse(string $sourcePath, array $expected): void
    {
        $this->assertExpectedArrayRecursive(
            $expected,
            (new MetadataParser())->parse($sourcePath)->getAll(),
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
            ],
            'jxl2.jxl' => [
                'url' => 'https://github.com/Exiv2/exiv2/raw/a7a9835/test/data/issue_2233_poc1.jxl',
                'meta' => [
                    'exif' => ['IFD0' => ['Software' => 'GIMP 2.99.11']],
                    'xmp' => ['http://purl.org/dc/elements/1.1/' => ['creator' => ['exiv2.org']]],
                ],
            ],
            'jxl3.jxl' => [
                'url' => 'https://github.com/Exiv2/exiv2/raw/a7a9835/test/data/issue_2233_poc2.jxl',
                'meta' => [
                    'exif' => ['IFD0' => ['Software' => 'GIMP 2.99.11']],
                    'xmp' => ['http://purl.org/dc/elements/1.1/' => ['creator' => ['exiv2.org']]],
                ],
            ],
            'avif1.avif' => [
                'url' => 'https://github.com/Exiv2/exiv2/raw/a7a9835/test/data/avif_exif_xmp.avif',
                'meta' => [
                    'exif' => ['IFD0' => ['Software' => 'GIMP 2.99.5']],
                    'xmp' => ['http://purl.org/dc/elements/1.1/' => ['creator' => ['type="Seq" Developer']]],
                ],
            ],
            'avif2.avif' => [
                'url' => 'https://github.com/Exiv2/exiv2/raw/a7a9835/test/data/avif_metadata2.avif',
                'meta' => [
                    'exif' => ['IFD0' => ['Software' => 'GIMP 2.99.5']],
                    'xmp' => ['http://purl.org/dc/elements/1.1/' => ['creator' => ['type="Seq" me']]],
                ],
            ],
            'heic1.heic' => [
                'url' => 'https://github.com/Exiv2/exiv2/raw/a7a9835/test/data/IMG_3578.heic',
                'meta' => [
                    'exif' => ['IFD0' => ['Software' => '13.4.1']],
                ],
            ],
            'heic2.heic' => [
                'url' => 'https://github.com/Exiv2/exiv2/raw/a7a9835/test/data/Stonehenge.heic',
                'meta' => [
                    'exif' => ['IFD0' => ['Software' => 'Ver.1.00 ']],
                    'xmp' => ['http://purl.org/dc/elements/1.1/' => ['description' => ['Classic View']]],
                ],
            ],
        ];

        foreach ($paths as $filename => $data) {
            $path = __DIR__.'/../fixtures/'.$filename;

            if (!file_exists($path)) {
                (new Filesystem())->mkdir(\dirname($path));
                copy($data['url'], $path);
            }

            yield [$path, $data['meta']];
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
}
