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
            $this->filterValue(
                $metadata->getFormat(self::NAME)['Copyright']
                ?? $metadata->getFormat(XmpFormat::NAME)['http://purl.org/dc/elements/1.1/']['rights']
                ?? $metadata->getFormat(IptcFormat::NAME)['2#116']
                ?? $metadata->getFormat(PngFormat::NAME)['Copyright']
                ?? $metadata->getFormat(XmpFormat::NAME)['http://ns.adobe.com/photoshop/1.0/']['Credit']
                ?? $metadata->getFormat(XmpFormat::NAME)['http://prismstandard.org/namespaces/prismusagerights/2.1/']['creditLine']
                ?? $metadata->getFormat(IptcFormat::NAME)['2#110']
                ?? $metadata->getFormat(PngFormat::NAME)['Disclaimer']
                ?? []
            )
        );

        $creator = implode(
            ', ',
            $this->filterValue(
                $metadata->getFormat(self::NAME)['Artist']
                ?? $metadata->getFormat(XmpFormat::NAME)['http://purl.org/dc/elements/1.1/']['creator']
                ?? $metadata->getFormat(IptcFormat::NAME)['2#080']
                ?? $metadata->getFormat(PngFormat::NAME)['Author']
                ?? $metadata->getFormat(XmpFormat::NAME)['http://ns.adobe.com/photoshop/1.0/']['Source']
                ?? $metadata->getFormat(IptcFormat::NAME)['2#115']
                ?? $metadata->getFormat(PngFormat::NAME)['Source']
                ?? $metadata->getFormat(XmpFormat::NAME)['http://xmp.gettyimages.com/gift/1.0/']['AssetID']
                ?? []
            )
        );

        if ('' === $creator && '' === $copyright) {
            $creator = implode(
                ', ',
                $this->filterValue(
                    $metadata->getFormat(XmpFormat::NAME)['http://purl.org/dc/elements/1.1/']['title']
                    ?? $metadata->getFormat(IptcFormat::NAME)['2#005']
                    ?? $metadata->getFormat(PngFormat::NAME)['Title']
                    ?? []
                )
            );
        }

        if ($creator === $copyright && !$metadata->getFormat(self::NAME)['Artist']) {
            $creator = '';
        }

        return $this->buildExif($copyright, $creator);
    }

    public function parse(string $binaryChunk): array
    {
        if (!\is_callable('exif_read_data')) {
            trigger_error('Missing PHP Exif extension. Install the extension or disable the preserveCopyrightMetadata option to get rid of this warning.', E_USER_WARNING);

            return [];
        }

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

    private function buildExif(string $copyright, string $artist): string
    {
        $data = [];

        if ('' !== $copyright) {
            $data["\x98\x82"] = $copyright;
        }

        if ('' !== $artist) {
            $data["\x3B\x01"] = $artist;
        }

        if (!$data) {
            return '';
        }

        // TODO: handle maxlength

        // Offset to data area
        $offset = \count($data) * 12 + 14;

        $exif = "II\x2A\x00"; // TIFF header Intel byte order (little endian)
        $exif .= pack('V', 8); // Offset to first IFD
        $exif .= pack('v', \count($data)); // Number of directory entries

        foreach ($data as $key => $value) {
            $exif .= (string) $key;
            $exif .= "\x02\x00"; // ASCII string
            $exif .= pack('V', \strlen($value)); // String size

            if (\strlen($value) > 4) {
                $exif .= pack('V', $offset); // Offset to data value
                $offset += \strlen($value);
            } else {
                $exif .= str_pad($value, 4, "\x00"); // 4 byte string
            }
        }

        $exif .= "\x00\x00\x00\x00"; // Last IFD

        foreach ($data as $value) {
            if (\strlen($value) > 4) {
                $exif .= $value; // Data area
            }
        }

        return $exif;
    }
}
