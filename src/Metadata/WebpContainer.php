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

class WebpContainer extends AbstractContainer
{
    public function getMagicBytes(): string
    {
        return "\x52\x49\x46\x46";
    }

    public function apply($inputStream, $outputStream, ImageMetadata $metadata, array $preserveKeysByFormat): void
    {
        $head = fread($inputStream, 12);
        $size = unpack('V', substr($head, 4, 4))[1];

        if ($size % 2 || !str_starts_with($head, 'RIFF') || 'WEBP' !== substr($head, 8)) {
            throw new InvalidImageContainerException('Invalid WEBP head');
        }

        $xmpChunk = '';
        $exifChunk = '';

        if ($xmp = $this->metadataReaderWriter->serializeFormat(XmpFormat::NAME, $metadata, $preserveKeysByFormat[XmpFormat::NAME] ?? [])) {
            $xmpChunk = $this->buildChunk('XMP ', $xmp);
        }

        if ($exif = $this->metadataReaderWriter->serializeFormat(ExifFormat::NAME, $metadata, $preserveKeysByFormat[ExifFormat::NAME] ?? [])) {
            $exifChunk = $this->buildChunk('EXIF', $exif);
        }

        // Update file size field
        $head = pack('A4VA4', 'RIFF', $size + \strlen($xmpChunk) + \strlen($exifChunk), 'WEBP');

        fwrite($outputStream, $head);

        stream_copy_to_stream($inputStream, $outputStream, $size - 4);

        fwrite($outputStream, $xmpChunk);
        fwrite($outputStream, $exifChunk);

        stream_copy_to_stream($inputStream, $outputStream);
    }

    public function parse($stream): array
    {
        $metadata = [];

        $head = fread($stream, 12);

        if (!str_starts_with($head, 'RIFF') || 'WEBP' !== substr($head, 8)) {
            throw new InvalidImageContainerException('Invalid WEBP head');
        }

        while (false !== $marker = fread($stream, 8)) {
            if ('' === $marker) {
                break;
            }

            if (8 !== \strlen($marker)) {
                throw new InvalidImageContainerException(sprintf('Invalid WEBP chunk "%s"', '0x'.bin2hex($marker)));
            }

            $type = substr($marker, 0, 4);
            $size = unpack('V', substr($marker, 4, 4))[1];

            if ('EXIF' === $type) {
                $metadata[ExifFormat::NAME] = $this->parseFormat(ExifFormat::NAME, fread($stream, $size));
            } elseif ('XMP ' === $type) {
                $metadata[XmpFormat::NAME] = $this->parseFormat(XmpFormat::NAME, fread($stream, $size));
            } else {
                // Skip to the next chunk
                fseek($stream, $size, SEEK_CUR);
            }

            // RIFF chunks are padded to an even number
            if ($size % 2) {
                fseek($stream, 1, SEEK_CUR);
            }
        }

        return $metadata;
    }

    private function buildChunk($type, $content): string
    {
        $size = \strlen($content);

        // RIFF chunks are padded to an even number
        if ($size % 2) {
            $content .= "\x00";
        }

        return pack('A4V', $type, $size).$content;
    }
}
