<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image\Metadata;

class ExifFormat extends AbstractFormat
{
    public const NAME = 'exif';

    public function serialize(ImageMetadata $metadata): string
    {
        $copyright = implode(
            ', ',
            (array) (
                $metadata->getFormat(self::NAME)['Copyright']
                ?? $metadata->getFormat(XmpFormat::NAME)['http://purl.org/dc/elements/1.1/']['rights']
                ?? $metadata->getFormat(IptcFormat::NAME)['2#116']
                ?? $metadata->getFormat(XmpFormat::NAME)['http://ns.adobe.com/photoshop/1.0/']['Credit']
                ?? $metadata->getFormat(XmpFormat::NAME)['http://prismstandard.org/namespaces/prismusagerights/2.1/']['creditLine']
                ?? $metadata->getFormat(IptcFormat::NAME)['2#110']
                ?? []
            )
        );

        $creator = implode(
            ', ',
            (array) (
                $metadata->getFormat(self::NAME)['Artist']
                ?? $metadata->getFormat(XmpFormat::NAME)['http://purl.org/dc/elements/1.1/']['creator']
                ?? $metadata->getFormat(IptcFormat::NAME)['2#080']
                ?? $metadata->getFormat(XmpFormat::NAME)['http://ns.adobe.com/photoshop/1.0/']['Source']
                ?? $metadata->getFormat(IptcFormat::NAME)['2#115']
                ?? $metadata->getFormat(IptcFormat::NAME)['2#005']
                ?? $metadata->getFormat(XmpFormat::NAME)['http://xmp.gettyimages.com/gift/1.0/']['AssetID']
                ?? $metadata->getFormat(XmpFormat::NAME)['http://purl.org/dc/elements/1.1/']['title']
                ?? []
            )
        );

        return $this->buildExif($copyright, $creator);
    }

    public function parse(string $binaryChunk): array
    {
        $app1 = "Exif\x00\x00$binaryChunk";

        $jpegStream = fopen('php://memory', 'r+');

        fwrite($jpegStream, "\xFF\xD8\xFF\xE1");
        fwrite($jpegStream, pack('n', \strlen($app1) + 2));
        fwrite($jpegStream, $app1);
        fwrite($jpegStream, "\xFF\xDA\x00\x02\xFF\xD9");

        rewind($jpegStream);

        $data = @exif_read_data($jpegStream, '', true);

        if (!\is_array($data)) {
            return [];
        }

        unset($data['FILE'], $data['COMPUTED']);

        return $data;
    }

    private function buildExif(string $copyright, string $artist)
    {
        $copyright = $copyright ?: ' ';
        $artist = $artist ?: ' ';

        // TODO: handle maxlength

        // Offset to data area
        $offset = 38;

        $exif = "II\x2A\x00"; // TIFF header Intel byte order (little endian)
        $exif .= pack('V', 8); // Offset to first IFD
        $exif .= pack('v', 2); // Number of directory entries

        $exif .= "\x98\x82"; // Copyright 0x8298
        $exif .= "\x02\x00"; // ASCII string
        $exif .= pack('V', \strlen($copyright)); // String size

        if (\strlen($copyright) > 4) {
            $exif .= pack('V', $offset); // Offset to data value
            $offset += \strlen($copyright);
        } else {
            $exif .= str_pad($copyright, 4, "\x00"); // 4 byte string
        }

        $exif .= "\x3B\x01"; // Artist 0x013B
        $exif .= "\x02\x00"; // ASCII string
        $exif .= pack('V', \strlen($artist)); // String size

        if (\strlen($artist) > 4) {
            $exif .= pack('V', $offset); // Offset to data value
        } else {
            $exif .= str_pad($artist, 4, "\x00"); // 4 byte string
        }

        $exif .= "\x00\x00\x00\x00"; // Last IFD

        if (\strlen($copyright) > 4) {
            $exif .= $copyright; // Data area
        }

        if (\strlen($artist) > 4) {
            $exif .= $artist; // Data area
        }

        return $exif;
    }
}
