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

use Contao\Image\Metadata\ImageMetadata;
use Contao\Image\Metadata\PngFormat;
use PHPUnit\Framework\TestCase;

class PngFormatTest extends TestCase
{
    /**
     * @dataProvider getParse
     */
    public function testParse(string $source, array $expected): void
    {
        $this->assertSame($expected, (new PngFormat())->parse($source));
        $this->assertSame($expected, (new PngFormat())->toReadable((new PngFormat())->parse($source)));
    }

    public function getParse(): \Generator
    {
        yield [
            "Copyright\x00© 2023 me",
            [
                'Copyright' => ['© 2023 me'],
            ],
        ];

        yield [
            "Keyword\x00Text",
            [
                'Keyword' => ['Text'],
            ],
        ];

        yield [
            "Copyright\x00With\x00byte and invalid UTF-8: \xD6sterreich",
            [
                'Copyright' => ["With\u{FFFD}byte and invalid UTF-8: \u{FFFD}sterreich"],
            ],
        ];
    }

    /**
     * @dataProvider getSerialize
     */
    public function testSerialize(ImageMetadata $source, array $preserveKeys, string $expected): void
    {
        $this->assertSame($expected, (new PngFormat())->serialize($source, $preserveKeys));
    }

    public function getSerialize(): \Generator
    {
        yield [
            new ImageMetadata([
                'png' => [
                    'Copyright' => 'Copyright',
                    'Author' => 'Author',
                    'Source' => 'Source',
                    'Disclaimer' => 'DisclaimerDisclaimerDisclaimer',
                    'Title' => 'Title',
                ],
            ]),
            PngFormat::DEFAULT_PRESERVE_KEYS,
            "\x00\x00\x00\x17iTXtCopyright\x00\x00\x00\x00\x00Copyright\xc5\x6d\x35\x49"
            ."\x00\x00\x00\x11iTXtAuthor\x00\x00\x00\x00\x00Author\x30\xb5\xcc\xfc"
            ."\x00\x00\x00\x11iTXtSource\x00\x00\x00\x00\x00Source\xec\x2d\x66\xd8"
            ."\x00\x00\x00\x24iTXtDisclaimer\x00\x01\x00\x00\x00"
            ."\x78\xda\x73\xc9\x2c\x4e\xce\x49\xcc\xcc\x4d\x2d\x72\xc1\xc2\x02\x00\xb7\x72\x0b\xf8\x43\xb9\x0f\x28",
        ];

        yield [
            new ImageMetadata([
                'iptc' => [
                    '2#116' => ['Copyright'],
                ],
                'exif' => [
                    'IFD0' => ['Artist' => 'Artist'],
                ],
                'xmp' => [
                    'http://ns.adobe.com/photoshop/1.0/' => [
                        'Credit' => 'Credit',
                    ],
                ],
            ]),
            PngFormat::DEFAULT_PRESERVE_KEYS,
            "\x00\x00\x00\x17iTXtCopyright\x00\x00\x00\x00\x00Copyright\xc5\x6d\x35\x49"
            ."\x00\x00\x00\x11iTXtAuthor\x00\x00\x00\x00\x00Artist\x8c\x43\x82\xb3"
            ."\x00\x00\x00\x15iTXtDisclaimer\x00\x00\x00\x00\x00Credit\x00\xb3\x23\x2f",
        ];

        yield [
            new ImageMetadata([
                'xmp' => [
                    'http://purl.org/dc/elements/1.1/' => [
                        'title' => ['Title'],
                    ],
                ],
            ]),
            PngFormat::DEFAULT_PRESERVE_KEYS,
            '',
        ];
    }
}
