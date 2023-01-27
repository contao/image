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
use Contao\Image\Metadata\ImageMetadata;
use PHPUnit\Framework\TestCase;

class ExifFormatTest extends TestCase
{
    /**
     * @dataProvider getParse
     */
    public function testParse(string $source, array $expected): void
    {
        $this->assertSame($expected, (new ExifFormat())->parse($source));
    }

    public function getParse(): \Generator
    {
        yield [
            // Little endian byte order
            "II*\x00\x08\x00\x00\x00\x02\x00"
            ."\x98\x82\x02\x00\x09\x00\x00\x00\x26\x00\x00\x00"
            ."\x3b\x01\x02\x00\x04\x00\x00\x00Arti"
            ."\x00\x00\x00\x00Copyright",
            [
                'IFD0' => [
                    'Copyright' => 'Copyright',
                    'Artist' => 'Arti',
                ],
            ],
        ];

        yield [
            // Big endian byte order
            "MM\x00*\x00\x00\x00\x08\x00\x02"
            ."\x82\x98\x00\x02\x00\x00\x00\x09\x00\x00\x00\x26"
            ."\x01\x3b\x00\x02\x00\x00\x00\x04Arti"
            ."\x00\x00\x00\x00Copyright",
            [
                'IFD0' => [
                    'Copyright' => 'Copyright',
                    'Artist' => 'Arti',
                ],
            ],
        ];
    }

    /**
     * @dataProvider getSerialize
     */
    public function testSerialize(ImageMetadata $source, array $preserveKeys, string $expected): void
    {
        $this->assertSame($expected, (new ExifFormat())->serialize($source, $preserveKeys));
    }

    public function getSerialize(): \Generator
    {
        yield [
            new ImageMetadata([
                'exif' => [
                    'IFD0' => [
                        'Copyright' => 'Copyright',
                        'Artist' => 'Arti', // Only four bytes
                    ],
                ],
            ]),
            ExifFormat::DEFAULT_PRESERVE_KEYS,
            "II*\x00\x08\x00\x00\x00\x02\x00"
            ."\x98\x82\x02\x00\x09\x00\x00\x00\x26\x00\x00\x00"
            ."\x3b\x01\x02\x00\x04\x00\x00\x00Arti"
            ."\x00\x00\x00\x00Copyright",
        ];

        yield [
            new ImageMetadata([
                'iptc' => [
                    '2#116' => ['Copyright'],
                ],
                'xmp' => [
                    'http://purl.org/dc/elements/1.1/' => [
                        'creator' => ['Creator 1', 'Creator 2'],
                    ],
                ],
            ]),
            ExifFormat::DEFAULT_PRESERVE_KEYS,
            "II*\x00\x08\x00\x00\x00\x02\x00"
            ."\x98\x82\x02\x00\x09\x00\x00\x00\x26\x00\x00\x00"
            ."\x3b\x01\x02\x00\x14\x00\x00\x00\x2f\x00\x00\x00"
            ."\x00\x00\x00\x00CopyrightCreator 1, Creator 2",
        ];

        yield [
            new ImageMetadata([
                'xmp' => [
                    'http://purl.org/dc/elements/1.1/' => [
                        'title' => ['Title'],
                    ],
                ],
            ]),
            ExifFormat::DEFAULT_PRESERVE_KEYS,
            '',
        ];
    }
}
