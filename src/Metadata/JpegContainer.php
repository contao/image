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

use Contao\Image\Exception\InvalidImageContainerException;

class JpegContainer extends AbstractContainer
{
    public function getMagicBytes(): string
    {
        return "\xFF\xD8\xFF";
    }

    public function apply($inputStream, $outputStream, ImageMetadata $metadata, array $preserveKeysByFormat): void
    {
        $xmp = $this->metadataReaderWriter->serializeFormat(XmpFormat::NAME, $metadata, $preserveKeysByFormat[XmpFormat::NAME] ?? []);
        $exif = $this->metadataReaderWriter->serializeFormat(ExifFormat::NAME, $metadata, $preserveKeysByFormat[ExifFormat::NAME] ?? []);
        $iptc = $this->metadataReaderWriter->serializeFormat(IptcFormat::NAME, $metadata, $preserveKeysByFormat[IptcFormat::NAME] ?? []);

        while (false !== $marker = fread($inputStream, 2)) {
            if (2 !== \strlen($marker) || "\xFF" !== $marker[0]) {
                throw new InvalidImageContainerException('Invalid JPEG marker');
            }

            // Start of scan marker
            if ("\xDA" === $marker[1]) {
                if ($xmp) {
                    fwrite($outputStream, $this->buildMarkerSegment("\xE1", "http://ns.adobe.com/xap/1.0/\x00$xmp"));
                }

                if ($exif) {
                    fwrite($outputStream, $this->buildMarkerSegment("\xE1", "Exif\x00\x00$exif"));
                }

                if ($iptc) {
                    fwrite($outputStream, $this->buildMarkerSegment("\xED", "Photoshop 3.0\x00$iptc"));
                }

                // Copy the rest of the image
                fwrite($outputStream, $marker);
                stream_copy_to_stream($inputStream, $outputStream);

                return;
            }

            fwrite($outputStream, $marker);

            // Skip two byte markers
            if (\ord($marker[1]) >= 0xD0 && \ord($marker[1]) <= 0xD9) {
                continue;
            }

            $sizeBytes = fread($inputStream, 2);
            fwrite($outputStream, $sizeBytes);

            $size = unpack('n', $sizeBytes)[1];

            stream_copy_to_stream($inputStream, $outputStream, $size - 2);
        }
    }

    public function parse($stream): array
    {
        $metadata = [];

        while (false !== $marker = fread($stream, 2)) {
            if (2 !== \strlen($marker) || "\xFF" !== $marker[0]) {
                throw new InvalidImageContainerException('Invalid JPEG marker');
            }

            // Skip two byte markers
            if (\ord($marker[1]) >= 0xD0 && \ord($marker[1]) <= 0xD9) {
                continue;
            }

            // Start of scan marker
            if ("\xDA" === $marker[1]) {
                break;
            }

            $size = unpack('n', fread($stream, 2))[1];

            if ("\xE1" === $marker[1]) {
                $metadata[] = $this->parseApp1(fread($stream, $size - 2));
                continue;
            }

            if ("\xED" === $marker[1]) {
                $metadata[] = $this->parseApp13(fread($stream, $size - 2));
                continue;
            }

            // Skip to the next marker
            fseek($stream, $size - 2, SEEK_CUR);
        }

        if (!$metadata) {
            return [];
        }

        return array_merge(...$metadata);
    }

    private function buildMarkerSegment($marker, $content): string
    {
        $size = pack('n', \strlen($content) + 2);

        return "\xFF$marker$size$content";
    }

    private function parseApp1(string $app1): array
    {
        if (str_starts_with($app1, "Exif\x00\x00")) {
            return [ExifFormat::NAME => $this->parseFormat(ExifFormat::NAME, substr($app1, 6))];
        }

        if (str_starts_with($app1, "http://ns.adobe.com/xap/1.0/\x00")) {
            return [XmpFormat::NAME => $this->parseFormat(XmpFormat::NAME, substr($app1, 29))];
        }

        return [];
    }

    private function parseApp13(string $app13): array
    {
        if (str_starts_with($app13, "Photoshop 3.0\x00")) {
            return [IptcFormat::NAME => $this->parseFormat(IptcFormat::NAME, substr($app13, 14))];
        }

        if (str_starts_with($app13, 'Adobe_Photoshop2.5:')) {
            return [IptcFormat::NAME => $this->parseFormat(IptcFormat::NAME, substr($app13, 19))];
        }

        return [];
    }
}
