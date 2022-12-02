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

use Contao\Image\Exception\RuntimeException;

class WebpContainer extends AbstractContainer
{
    public function __construct(private MetadataParser $parser)
    {
    }

    public function getMagicBytes(): string
    {
        return "\x52\x49\x46\x46";
    }

    public function apply($inputStream, $outputStream, ImageMetadata $metadata): void
    {
        $head = fread($inputStream, 12);
        $size = unpack('V', substr($head, 4, 4))[1];

        if ($size % 2 || !str_starts_with($head, 'RIFF') || 'WEBP' !== substr($head, 8)) {
            throw new RuntimeException('Invalid WEBP');
        }

        $xmp = $this->buildChunk('XMP ', $this->parser->serializeFormat(XmpFormat::NAME, $metadata));
        $exif = $this->buildChunk('EXIF', $this->parser->serializeFormat(ExifFormat::NAME, $metadata));

        // Update file size field
        $head = pack('A4VA4', 'RIFF', $size + \strlen($xmp) + \strlen($exif), 'WEBP');

        fwrite($outputStream, $head);

        stream_copy_to_stream($inputStream, $outputStream, $size - 4);

        fwrite($outputStream, $xmp);
        fwrite($outputStream, $exif);

        stream_copy_to_stream($inputStream, $outputStream);
    }

    public function parse($stream): array
    {
        $metadata = [];

        $head = fread($stream, 12);

        if (!str_starts_with($head, 'RIFF') || 'WEBP' !== substr($head, 8)) {
            throw new RuntimeException('Invalid WEBP');
        }

        while (false !== $marker = fread($stream, 8)) {
            if ('' === $marker) {
                break;
            }

            if (8 !== \strlen($marker)) {
                throw new RuntimeException(sprintf('Invalid chunk "%s"', '0x'.bin2hex($marker)));
            }

            $type = substr($marker, 0, 4);
            $size = unpack('V', substr($marker, 4, 4))[1];

            if ('EXIF' === $type) {
                $metadata[ExifFormat::NAME] = $this->parser->parseFormat(ExifFormat::NAME, fread($stream, $size));
            } elseif ('XMP ' === $type) {
                $metadata[XmpFormat::NAME] = $this->parser->parseFormat(XmpFormat::NAME, fread($stream, $size));
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
