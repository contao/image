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

use Contao\Image\Metadata\GifFormat;
use Contao\Image\Metadata\ImageMetadata;
use PHPUnit\Framework\TestCase;

class GifFormatTest extends TestCase
{
    /**
     * @dataProvider getParse
     */
    public function testParse(string $source, array $expected): void
    {
        $this->assertSame($expected, (new GifFormat())->parse($source));
        $this->assertSame($expected, (new GifFormat())->toReadable((new GifFormat())->parse($source)));
    }

    public function getParse(): \Generator
    {
        yield [
            "\x04Test\x00",
            [
                'Comment' => ['Test'],
            ],
        ];

        yield [
            "\x01T\x02es\x01t\x00",
            [
                'Comment' => ['Test'],
            ],
        ];

        yield [
            "\x01T\x03e\x00s\x01t\x00",
            [
                'Comment' => ["Te\u{FFFD}st"],
            ],
        ];
    }

    /**
     * @dataProvider getSerialize
     */
    public function testSerialize(ImageMetadata $source, array $preserveKeys, string $expected): void
    {
        $this->assertSame($expected, (new GifFormat())->serialize($source, $preserveKeys));
    }

    public function getSerialize(): \Generator
    {
        yield [
            new ImageMetadata([
                'gif' => [
                    'Comment' => ['Comment'],
                ],
            ]),
            GifFormat::DEFAULT_PRESERVE_KEYS,
            "\x21\xFE\x07Comment\x00",
        ];

        yield [
            new ImageMetadata([
                'iptc' => [
                    '2#116' => ['Copyright'],
                ],
            ]),
            GifFormat::DEFAULT_PRESERVE_KEYS,
            "\x21\xFE\x09Copyright\x00",
        ];

        yield [
            new ImageMetadata([
                'gif' => [
                    'Comment' => ['Comment'.str_repeat('.', 255)],
                ],
            ]),
            GifFormat::DEFAULT_PRESERVE_KEYS,
            "\x21\xFE\xFFComment".str_repeat('.', 248)."\x07.......\x00",
        ];

        yield [
            new ImageMetadata([
                'gif' => [
                    'Comment' => ['Comment 1', 'Comment 2'],
                ],
            ]),
            GifFormat::DEFAULT_PRESERVE_KEYS,
            "\x21\xFE\x09Comment 1\x00\x21\xFE\x09Comment 2\x00",
        ];

        yield [
            new ImageMetadata([
                'xmp' => [
                    'http://purl.org/dc/elements/1.1/' => [
                        'title' => ['Title'],
                    ],
                ],
            ]),
            GifFormat::DEFAULT_PRESERVE_KEYS,
            '',
        ];
    }
}
