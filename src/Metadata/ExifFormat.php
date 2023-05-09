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

use Contao\Image\Exception\InvalidImageMetadataException;
use Contao\Image\Exception\RuntimeException;

class ExifFormat extends AbstractFormat
{
    public const NAME = 'exif';
    public const DEFAULT_PRESERVE_KEYS = [
        'IFD0' => ['Copyright', 'Artist'],
    ];

    public function serialize(ImageMetadata $metadata, array $preserveKeys): string
    {
        $copyright = implode(
            ', ',
            $this->filterValue(
                $metadata->getFormat(self::NAME)['IFD0']['Copyright']
                ?? $metadata->getFormat(XmpFormat::NAME)['http://purl.org/dc/elements/1.1/']['rights']
                ?? $metadata->getFormat(IptcFormat::NAME)['2#116']
                ?? $metadata->getFormat(PngFormat::NAME)['Copyright']
                ?? $metadata->getFormat(XmpFormat::NAME)['http://ns.adobe.com/photoshop/1.0/']['Credit']
                ?? $metadata->getFormat(XmpFormat::NAME)['http://prismstandard.org/namespaces/prismusagerights/2.1/']['creditLine']
                ?? $metadata->getFormat(IptcFormat::NAME)['2#110']
                ?? $metadata->getFormat(PngFormat::NAME)['Disclaimer']
                ?? $metadata->getFormat(GifFormat::NAME)['Comment']
                ?? []
            )
        );

        $creator = implode(
            ', ',
            $this->filterValue(
                $metadata->getFormat(self::NAME)['IFD0']['Artist']
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

        if ($creator === $copyright && !($metadata->getFormat(self::NAME)['IFD0']['Artist'] ?? null)) {
            $creator = '';
        }

        if (!\in_array('Copyright', $preserveKeys['IFD0'] ?? [], true)) {
            $copyright = '';
        }

        if (!\in_array('Artist', $preserveKeys['IFD0'] ?? [], true)) {
            $creator = '';
        }

        return $this->buildExif($copyright, $creator);
    }

    public function parse(string $binaryChunk): array
    {
        if (!\function_exists('exif_read_data') && !class_exists('Imagick')) {
            trigger_error('Missing PHP Exif extension. Install the extension or disable the preserveCopyrightMetadata option to get rid of this warning.', E_USER_WARNING);

            return [];
        }

        $app1 = "Exif\x00\x00$binaryChunk";

        $jpegStream = fopen('php://memory', 'r+');

        fwrite($jpegStream, "\xFF\xD8\xFF\xE1");
        fwrite($jpegStream, pack('n', \strlen($app1) + 2));
        fwrite($jpegStream, $app1);
        fwrite(
            $jpegStream,
            "\xFF\xDB\x00\x43\x00\x03\x02\x02\x02\x02\x02\x03\x02\x02\x02\x03"
            ."\x03\x03\x03\x04\x06\x04\x04\x04\x04\x04\x08\x06\x06\x05\x06\x09"
            ."\x08\x0A\x0A\x09\x08\x09\x09\x0A\x0C\x0F\x0C\x0A\x0B\x0E\x0B\x09"
            ."\x09\x0D\x11\x0D\x0E\x0F\x10\x10\x11\x10\x0A\x0C\x12\x13\x12\x10"
            ."\x13\x0F\x10\x10\x10\xFF\xC9\x00\x0B\x08\x00\x01\x00\x01\x01\x01"
            ."\x11\x00\xFF\xCC\x00\x06\x00\x10\x10\x05\xFF\xDA\x00\x08\x01\x01"
            ."\x00\x00\x3F\x00\xD2\xCF\x20\xFF\xD9"
        );

        rewind($jpegStream);

        $data = [];

        // Fallback to Imagick if ext-exif is missing
        if (!\function_exists('exif_read_data')) {
            $image = new \Imagick();
            $image->readImageFile($jpegStream);

            foreach ($image->getImageProperties('exif:*') as $key => $value) {
                if ($value) {
                    $data['IFD0'][substr($key, 5)] = $value;
                }
            }

            if (!$data) {
                throw new InvalidImageMetadataException('Parsing Exif metadata failed');
            }

            return $this->toUtf8($data);
        }

        $data = @exif_read_data($jpegStream, '', true) ?: [];

        unset($data['FILE'], $data['COMPUTED']);

        if (!$data) {
            throw new InvalidImageMetadataException('Parsing Exif metadata failed');
        }

        return $this->toUtf8($data);
    }

    public function toReadable(array $data): array
    {
        return parent::toReadable(array_merge([], ...array_values($data)));
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

        if ($offset >= 2 ** 32) {
            throw new RuntimeException('Exif data too long');
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
