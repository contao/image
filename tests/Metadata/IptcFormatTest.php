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

use Contao\Image\Exception\InvalidImageMetadataException;
use Contao\Image\Metadata\ImageMetadata;
use Contao\Image\Metadata\IptcFormat;
use PHPUnit\Framework\TestCase;

class IptcFormatTest extends TestCase
{
    /**
     * @dataProvider getParse
     */
    public function testParse(string $source, array $expected, array $expectedReadable): void
    {
        if (!$expected) {
            $this->expectException(InvalidImageMetadataException::class);
        }

        $this->assertSame($expected, (new IptcFormat())->parse($source));
        $this->assertSame($expectedReadable, (new IptcFormat())->toReadable((new IptcFormat())->parse($source)));
    }

    public function getParse(): \Generator
    {
        yield [
            "8BIM\x04\x04\x00\x00\x00\x00\x00\x38"
            ."\x1c\x01\x5a\x00\x03\x1b\x25\x47"
            ."\x1c\x02\x74\x00\x09Copyright"
            ."\x1c\x02\x50\x00\x07Creator"
            ."\x1c\x02\x73\x00\x06Source"
            ."\x1c\x02\x6e\x00\x06Credit",
            [
                '1#090' => ["\x1b\x25\x47"],
                '2#116' => ['Copyright'],
                '2#080' => ['Creator'],
                '2#115' => ['Source'],
                '2#110' => ['Credit'],
            ],
            [
                'CopyrightNotice' => ['Copyright'],
                'By-line' => ['Creator'],
                'Source' => ['Source'],
                'Credit' => ['Credit'],
            ],
        ];

        yield [
            "8BIM\x04\x04\x00\x00\x00\x00\x00\x48"
            ."\x1c\x01\x5a\x00\x03\x1b\x25\x47"
            ."\x1c\x02\x74\x00\x0eCopyright 💩"
            ."\x1c\x02\x50\x00\x07Creator"
            ."\x1c\x02\x73\x00\x06Source"
            ."\x1c\x02\x6e\x00\x06Credit"
            ."\x1c\x02\x6e\x00\x06Credit",
            [
                '1#090' => ["\x1b\x25\x47"],
                '2#116' => ['Copyright 💩'],
                '2#080' => ['Creator'],
                '2#115' => ['Source'],
                '2#110' => ['Credit', 'Credit'],
            ],
            [
                'CopyrightNotice' => ['Copyright 💩'],
                'By-line' => ['Creator'],
                'Source' => ['Source'],
                'Credit' => ['Credit', 'Credit'],
            ],
        ];

        yield [
            'NOT IPTC',
            [],
            [],
        ];
    }

    /**
     * @dataProvider getSerialize
     */
    public function testSerialize(ImageMetadata $source, array $preserveKeys, string $expected): void
    {
        $this->assertSame($expected, (new IptcFormat())->serialize($source, $preserveKeys));
    }

    public function getSerialize(): \Generator
    {
        yield [
            new ImageMetadata([
                'iptc' => [
                    '2#116' => ['Copyright'],
                    '2#080' => ['Creator'],
                    '2#115' => ['Source'],
                    '2#110' => ['', 'Credit'],
                ],
            ]),
            IptcFormat::DEFAULT_PRESERVE_KEYS,
            "8BIM\x04\x04\x00\x00\x00\x00\x00\x38"
            ."\x1c\x01\x5a\x00\x03\x1b\x25\x47"
            ."\x1c\x02\x74\x00\x09Copyright"
            ."\x1c\x02\x50\x00\x07Creator"
            ."\x1c\x02\x73\x00\x06Source"
            ."\x1c\x02\x6e\x00\x06Credit",
        ];

        yield [
            new ImageMetadata([
                'exif' => [
                    'IFD0' => [
                        'Copyright' => 'Copyright',
                    ],
                ],
                'xmp' => [
                    'http://purl.org/dc/elements/1.1/' => [
                        'creator' => ['Creator 1', 'Creator 2'],
                    ],
                ],
            ]),
            IptcFormat::DEFAULT_PRESERVE_KEYS,
            "8BIM\x04\x04\x00\x00\x00\x00\x00\x32"
            ."\x1c\x01\x5a\x00\x03\x1b\x25\x47"
            ."\x1c\x02\x74\x00\x09Copyright"
            ."\x1c\x02\x50\x00\x09Creator 1"
            ."\x1c\x02\x50\x00\x09Creator 2",
        ];

        yield [
            new ImageMetadata([
                'iptc' => [
                    '2#116' => ['Maxlength'.str_repeat('.', 256)],
                    '2#080' => ['Maxlength'.str_repeat('.', 256)],
                ],
            ]),
            IptcFormat::DEFAULT_PRESERVE_KEYS,
            "8BIM\x04\x04\x00\x00\x00\x00\x00\xb2"
            ."\x1c\x01\x5a\x00\x03\x1b\x25\x47"
            ."\x1c\x02\x74\x00\x80Maxlength".str_repeat('.', 128 - 9)
            ."\x1c\x02\x50\x00\x20Maxlength".str_repeat('.', 32 - 9),
        ];

        yield [
            new ImageMetadata([
                'xmp' => [
                    'http://purl.org/dc/elements/1.1/' => [
                        'title' => ['Title'],
                    ],
                ],
            ]),
            IptcFormat::DEFAULT_PRESERVE_KEYS,
            '',
        ];
    }
}
