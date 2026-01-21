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
use Contao\Image\Exception\RuntimeException;

class WebpContainer extends AbstractContainer
{
    public function getMagicBytes(): string
    {
        return "\x52\x49\x46\x46";
    }

    public function apply($inputStream, $outputStream, ImageMetadata $metadata, array $preserveKeysByFormat): void
    {
        $head = fread($inputStream, 12);
        $outputSize = $size = unpack('V', substr($head, 4, 4))[1];

        if ($size % 2 || !str_starts_with($head, 'RIFF') || 'WEBP' !== substr($head, 8)) {
            throw new InvalidImageContainerException('Invalid WEBP head');
        }

        $vp8xMetaFlags = 0;
        $exifChunk = '';
        $xmpChunk = '';

        if ($exif = $this->metadataReaderWriter->serializeFormat(ExifFormat::NAME, $metadata, $preserveKeysByFormat[ExifFormat::NAME] ?? [])) {
            $exifChunk = $this->buildChunk('EXIF', $exif);
            // Exif metadata (E)
            $vp8xMetaFlags |= 0b00001000;
            $outputSize += \strlen($exifChunk);
        }

        if ($xmp = $this->metadataReaderWriter->serializeFormat(XmpFormat::NAME, $metadata, $preserveKeysByFormat[XmpFormat::NAME] ?? [])) {
            $xmpChunk = $this->buildChunk('XMP ', $xmp);
            // XMP metadata (X)
            $vp8xMetaFlags |= 0b00000100;
            $outputSize += \strlen($xmpChunk);
        }

        // Keep the original file if there are no metadata chunks to add
        if (!$exifChunk && !$xmpChunk) {
            fwrite($outputStream, $head);
            stream_copy_to_stream($inputStream, $outputStream);

            return;
        }

        $size -= 4;
        $firstChunk = true;

        while ($size >= 8) {
            $chunkFourcc = fread($inputStream, 4);
            $chunkSize = unpack('V', fread($inputStream, 4))[1];
            $size -= 8 + $chunkSize;

            if ($firstChunk) {
                $firstChunk = false;

                if ('VP8X' !== $chunkFourcc) {
                    $outputSize += 18;
                }

                fwrite($outputStream, pack('A4VA4', 'RIFF', $outputSize, 'WEBP'));

                if ('VP8 ' === $chunkFourcc) {
                    $vp8Header = fread($inputStream, 10);
                    fwrite($outputStream, $this->buildVp8xChunkFromVp8($vp8Header, $vp8xMetaFlags));
                    fwrite($outputStream, pack('A4V', $chunkFourcc, $chunkSize));
                    fwrite($outputStream, $vp8Header);
                    stream_copy_to_stream($inputStream, $outputStream, $chunkSize - 10);
                } elseif ('VP8L' === $chunkFourcc) {
                    $vp8lHeader = fread($inputStream, 5);
                    fwrite($outputStream, $this->buildVp8xChunkFromVp8l($vp8lHeader, $vp8xMetaFlags));
                    fwrite($outputStream, pack('A4V', $chunkFourcc, $chunkSize));
                    fwrite($outputStream, $vp8lHeader);
                    stream_copy_to_stream($inputStream, $outputStream, $chunkSize - 5);
                } elseif ('VP8X' === $chunkFourcc) {
                    fwrite($outputStream, pack('A4V', $chunkFourcc, $chunkSize));
                    $vp8xFlags = \ord(fread($inputStream, 1)) | $vp8xMetaFlags;
                    fwrite($outputStream, \chr($vp8xFlags));
                    stream_copy_to_stream($inputStream, $outputStream, $chunkSize - 1);
                } else {
                    throw new InvalidImageContainerException('Invalid WEBP chunk at start position');
                }
            } else {
                if (\in_array($chunkFourcc, ['EXIF', 'XMP ', "XMP\x00"], true)) {
                    throw new RuntimeException('Overwriting existing metadata chunks in WEBP is not supported');
                }

                fwrite($outputStream, pack('A4V', $chunkFourcc, $chunkSize));
                stream_copy_to_stream($inputStream, $outputStream, $chunkSize);
            }

            // RIFF chunks are padded to an even number
            if ($chunkSize % 2) {
                stream_copy_to_stream($inputStream, $outputStream, 1);
                --$size;
            }
        }

        if (0 !== $size) {
            throw new InvalidImageContainerException('Invalid WEBP chunks');
        }

        fwrite($outputStream, $exifChunk);
        fwrite($outputStream, $xmpChunk);
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
                $exif = fread($stream, $size);

                // Older ImageMagick versions incorrectly included the JPEG APP1
                // Exif identifier string 45 78 69 66 00 00
                // see https://github.com/ImageMagick/ImageMagick/issues/3140
                if (str_starts_with($exif, "Exif\x00\x00")) {
                    $exif = substr($exif, 6);
                }

                $metadata[ExifFormat::NAME] = $this->parseFormat(ExifFormat::NAME, $exif);
            } elseif ('XMP ' === $type || "XMP\x00" === $type) {
                // Older ImageMagick versions incorrectly used XMP followed by a null byte: 58 4D 50 00
                // see https://github.com/ImageMagick/ImageMagick/commit/da99bea66a4ad0bcf6149170eda81e6dcc229af0
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

    private function buildVp8xChunkFromVp8(string $header, int $vp8xMetaFlags): string
    {
        if (\ord($header[0]) & 0b00000001) {
            throw new InvalidImageContainerException('Expected VP8 keyframe');
        }

        if ("\x9D\x01\x2A" !== substr($header, 3, 3)) {
            throw new InvalidImageContainerException('Invalid VP8 keyframe sync code');
        }

        $width = unpack('v', substr($header, 6, 2))[1] & 0b0011111111111111;
        $height = unpack('v', substr($header, 8, 2))[1] & 0b0011111111111111;

        return $this->buildVp8xChunk(
            0b00000000 | $vp8xMetaFlags,
            $width,
            $height
        );
    }

    private function buildVp8xChunkFromVp8l(string $header, int $vp8xMetaFlags): string
    {
        if ("\x2F" !== $header[0]) {
            throw new InvalidImageContainerException('Invalid VP8L signature');
        }

        $data = unpack('V', substr($header, 1, 4))[1];

        if (0 !== $data >> 29) {
            throw new InvalidImageContainerException('Invalid VP8L version');
        }

        $width = ($data & 0b0011111111111111) + 1;
        $height = ($data >> 14 & 0b0011111111111111) + 1;

        // Alpha (L)
        if ($data >> 28 & 0b01) {
            $vp8xMetaFlags |= 0b00010000;
        }

        return $this->buildVp8xChunk(
            0b00000000 | $vp8xMetaFlags,
            $width,
            $height
        );
    }

    private function buildVp8xChunk(int $vp8xFlags, int $width, int $height): string
    {
        --$width;
        --$height;

        $vp8x = pack(
            'C*',
            $vp8xFlags, // Bit flags: |R|R|I|L|E|X|A|R|
            0,
            0,
            0,
            $width & 0xFF,
            $width >> 8 & 0xFF,
            $width >> 16 & 0xFF,
            $height & 0xFF,
            $height >> 8 & 0xFF,
            $height >> 16 & 0xFF
        );

        return $this->buildChunk('VP8X', $vp8x);
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
