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
use Contao\Image\Metadata\XmpFormat;
use PHPUnit\Framework\TestCase;

class XmpFormatTest extends TestCase
{
    /**
     * @dataProvider getParse
     */
    public function testParse(string $source, array $expected, array $expectedReadable): void
    {
        $wrapped =
            "<?xpacket begin=\"\u{FEFF}\" id=\"W5M0MpCehiHzreSzNTczkc9d\"?>"
            .'<x:xmpmeta xmlns:x="adobe:ns:meta/">'
            .$source
            .'</x:xmpmeta>'
            .'<?xpacket end="w"?>'
        ;

        if (!$expected) {
            $this->expectException(InvalidImageMetadataException::class);
        }

        $this->assertSame($expected, (new XmpFormat())->parse($source));
        $this->assertSame($expected, (new XmpFormat())->parse($wrapped));
        $this->assertSame($expectedReadable, (new XmpFormat())->toReadable((new XmpFormat())->parse($source)));
    }

    public function getParse(): \Generator
    {
        yield [
            '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">'
            .'<rdf:Description xmlns:dc="http://purl.org/dc/elements/1.1/" dc:rights="Minimal"/>'
            .'</rdf:RDF>',
            [
                'http://purl.org/dc/elements/1.1/' => [
                    'rights' => ['Minimal'],
                ],
            ],
            [
                'dc:rights' => ['Minimal'],
            ],
        ];

        yield [
            '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">'
            .'<rdf:Description '
            .'xmlns:dc="http://purl.org/dc/elements/1.1/" dc:rights="Rights" '
            .'xmlns:xmpl="https://example.com/" xmpl:test="Test" '
            .'xmlns:photoshop="http://ns.adobe.com/photoshop/1.0/" '
            .'>'
            .'<dc:creator><rdf:Seq><rdf:li>Creator 1</rdf:li><rdf:li>Creator 2</rdf:li></rdf:Seq></dc:creator>'
            .'<dc:title><rdf:Alt><rdf:li xml:lang="de">Bild</rdf:li><rdf:li xml:lang="en">Image</rdf:li></rdf:Alt></dc:title>'
            .'<photoshop:Credit><rdf:Bag><rdf:li>Credit 1</rdf:li><rdf:li>Credit 2</rdf:li></rdf:Bag></photoshop:Credit>'
            .'<xmpl:node>Node Test</xmpl:node>'
            .'</rdf:Description>'
            .'</rdf:RDF>',
            [
                'http://purl.org/dc/elements/1.1/' => [
                    'rights' => ['Rights'],
                    'creator' => ['Creator 1', 'Creator 2'],
                    'title' => ['Bild', 'Image'],
                ],
                'https://example.com/' => [
                    'test' => ['Test'],
                    'node' => ['Node Test'],
                ],
                'http://ns.adobe.com/photoshop/1.0/' => [
                    'Credit' => ['Credit 1', 'Credit 2'],
                ],
            ],
            [
                'dc:rights' => ['Rights'],
                'dc:creator' => ['Creator 1', 'Creator 2'],
                'dc:title' => ['Bild', 'Image'],
                'https://example.com/:test' => ['Test'],
                'https://example.com/:node' => ['Node Test'],
                'photoshop:Credit' => ['Credit 1', 'Credit 2'],
            ],
        ];

        yield [
            '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">malformed',
            [],
            [],
        ];

        yield [
            'NOT XMP',
            [],
            [],
        ];
    }

    /**
     * @dataProvider getSerialize
     */
    public function testSerialize(ImageMetadata $source, array $preserveKeys, string $expected): void
    {
        $this->assertSame($expected, (new XmpFormat())->serialize($source, $preserveKeys));
    }

    public function getSerialize(): \Generator
    {
        yield [
            new ImageMetadata([
                'xmp' => [
                    'http://purl.org/dc/elements/1.1/' => [
                        'rights' => ['Rights'],
                        'creator' => ['Creator 1', 'Creator 2'],
                        'title' => ['Some long title...'],
                    ],
                    'http://ns.adobe.com/photoshop/1.0/' => [
                        'Credit' => '',
                    ],
                ],
            ]),
            XmpFormat::DEFAULT_PRESERVE_KEYS,
            "<?xpacket begin=\"\u{FEFF}\" id=\"W5M0MpCehiHzreSzNTczkc9d\"?>"
            .'<x:xmpmeta xmlns:x="adobe:ns:meta/">'
            .'<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">'
            .'<rdf:Description xmlns:dc="http://purl.org/dc/elements/1.1/" dc:rights="Rights">'
            .'<dc:creator><rdf:Bag><rdf:li>Creator 1</rdf:li><rdf:li>Creator 2</rdf:li></rdf:Bag></dc:creator>'
            .'</rdf:Description>'
            .'</rdf:RDF>'
            .'</x:xmpmeta>'
            .'<?xpacket end="w"?>',
        ];

        yield [
            new ImageMetadata([
                'xmp' => [
                    'http://purl.org/dc/elements/1.1/' => [
                        'rights' => ['Rights'],
                        'creator' => ['Creator 1', 'Creator 2'],
                        'title' => ['Some long title...'],
                    ],
                    'http://ns.adobe.com/photoshop/1.0/' => [
                        'Credit' => 'Credit',
                    ],
                ],
            ]),
            ['http://purl.org/dc/elements/1.1/' => ['title']],
            "<?xpacket begin=\"\u{FEFF}\" id=\"W5M0MpCehiHzreSzNTczkc9d\"?>"
            .'<x:xmpmeta xmlns:x="adobe:ns:meta/">'
            .'<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">'
            .'<rdf:Description xmlns:dc="http://purl.org/dc/elements/1.1/" dc:title="Some long title..."/>'
            .'</rdf:RDF>'
            .'</x:xmpmeta>'
            .'<?xpacket end="w"?>',
        ];

        yield [
            new ImageMetadata([
                'iptc' => [
                    '2#116' => ['Copyright'],
                ],
                'exif' => [
                    'IFD0' => ['Artist' => 'Artist'],
                ],
                'png' => [
                    'Source' => ['Source'],
                ],
            ]),
            XmpFormat::DEFAULT_PRESERVE_KEYS,
            "<?xpacket begin=\"\u{FEFF}\" id=\"W5M0MpCehiHzreSzNTczkc9d\"?>"
            .'<x:xmpmeta xmlns:x="adobe:ns:meta/">'
            .'<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">'
            .'<rdf:Description '
            .'xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:photoshop="http://ns.adobe.com/photoshop/1.0/" '
            .'dc:rights="Copyright" dc:creator="Artist" photoshop:Source="Source"'
            .'/>'
            .'</rdf:RDF>'
            .'</x:xmpmeta>'
            .'<?xpacket end="w"?>',
        ];

        yield [
            new ImageMetadata([
                'xmp' => [
                    'http://purl.org/dc/elements/1.1/' => [
                        'title' => ['Some long title...'],
                    ],
                ],
            ]),
            XmpFormat::DEFAULT_PRESERVE_KEYS,
            '',
        ];
    }

    public function testSerializeValuesWithAmpersands(): void
    {
        $this->assertSame(
            "<?xpacket begin=\"\u{FEFF}\" id=\"W5M0MpCehiHzreSzNTczkc9d\"?>"
            .'<x:xmpmeta xmlns:x="adobe:ns:meta/">'
            .'<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">'
            .'<rdf:Description '
            .'xmlns:photoshop="http://ns.adobe.com/photoshop/1.0/" xmlns:dc="http://purl.org/dc/elements/1.1/" '
            .'photoshop:Credit="&amp;copy"'
            .'>'
            .'<dc:rights xmlns:dc="http://purl.org/dc/elements/1.1/">'
            .'<rdf:Bag><rdf:li>Rights</rdf:li><rdf:li>&amp; more</rdf:li></rdf:Bag>'
            .'</dc:rights>'
            .'<dc:creator xmlns:dc="http://purl.org/dc/elements/1.1/">'
            .'<rdf:Bag><rdf:li>Creator 1</rdf:li><rdf:li>&amp;crea</rdf:li></rdf:Bag>'
            .'</dc:creator>'
            .'</rdf:Description>'
            .'</rdf:RDF>'
            .'</x:xmpmeta>'
            .'<?xpacket end="w"?>',
            (new XmpFormat())->serialize(
                new ImageMetadata([
                    'xmp' => [
                        'http://purl.org/dc/elements/1.1/' => [
                            'rights' => ['Rights', '& more'],
                            'creator' => ['Creator 1', '&crea'],
                            'title' => ['Some long title...'],
                        ],
                        'http://ns.adobe.com/photoshop/1.0/' => [
                            'Credit' => '&copy',
                        ],
                    ],
                ]),
                XmpFormat::DEFAULT_PRESERVE_KEYS
            )
        );
    }
}
