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

class GifContainer extends AbstractContainer
{
    public function getMagicBytes(): string
    {
        return "\x47\x49\x46\x38";
    }

    public function apply($inputStream, $outputStream, ImageMetadata $metadata, array $preserveKeysByFormat): void
    {
        $xmp = $this->metadataReaderWriter->serializeFormat(XmpFormat::NAME, $metadata, $preserveKeysByFormat[XmpFormat::NAME] ?? []);
        $gif = $this->metadataReaderWriter->serializeFormat(GifFormat::NAME, $metadata, $preserveKeysByFormat[GifFormat::NAME] ?? []);

        $head = fread($inputStream, 13);

        if (!str_starts_with($head, 'GIF') || 13 !== \strlen($head)) {
            throw new InvalidImageContainerException('Invalid GIF head');
        }

        if (!\in_array(substr($head, 3, 3), ['87a', '89a'], true)) {
            throw new InvalidImageContainerException(sprintf('Unrecognized GIF version "%s".', substr($head, 3, 3)));
        }

        // Application and comment extension require version 89a
        if ($xmp || $gif) {
            $head[4] = '9';
        }

        fwrite($outputStream, $head);

        // Skip Global Color Table
        if (\ord($head[10]) & 0b10000000) {
            stream_copy_to_stream($inputStream, $outputStream, 3 * (2 ** (1 + (\ord($head[10]) & 0b00000111))));
        }

        // Skip extension blocks
        while ("\x21" === $marker = fread($inputStream, 1)) {
            fwrite($outputStream, $marker);

            // Skip label
            stream_copy_to_stream($inputStream, $outputStream, 1);

            while ("\x00" !== $subBlockSize = fread($inputStream, 1)) {
                if (1 !== \strlen($subBlockSize)) {
                    throw new InvalidImageContainerException('Invalid GIF sub block');
                }

                fwrite($outputStream, $subBlockSize);
                stream_copy_to_stream($inputStream, $outputStream, \ord($subBlockSize));
            }

            fwrite($outputStream, $subBlockSize);
        }

        if ($xmp) {
            $magicTrailer = "\x01".implode('', range("\xFF", "\x00"))."\x00";
            fwrite($outputStream, "\x21\xFF\x0BXMP DataXMP$xmp$magicTrailer");
        }

        if ($gif) {
            fwrite($outputStream, $gif);
        }

        // Copy the rest of the image
        fwrite($outputStream, $marker);
        stream_copy_to_stream($inputStream, $outputStream);
    }

    public function parse($stream): array
    {
        $metadata = [];

        $head = fread($stream, 13);

        if (!str_starts_with($head, 'GIF') || 13 !== \strlen($head)) {
            throw new InvalidImageContainerException('Invalid GIF head');
        }

        if (!\in_array(substr($head, 3, 3), ['87a', '89a'], true)) {
            throw new InvalidImageContainerException(sprintf('Unrecognized GIF version "%s".', substr($head, 3, 3)));
        }

        // Skip Global Color Table
        if (\ord($head[10]) & 0b10000000) {
            fseek($stream, 3 * (2 ** (1 + (\ord($head[10]) & 0b00000111))), SEEK_CUR);
        }

        while (false !== $marker = fread($stream, 1)) {
            // Trailer
            if ("\x3B" === $marker) {
                break;
            }

            // Image descriptor block
            if ("\x2C" === $marker) {
                $imageDescriptor = fread($stream, 9);

                // Skip local color table
                if (\ord($imageDescriptor[8]) & 0b10000000) {
                    fseek($stream, 3 * (2 ** (1 + (\ord($imageDescriptor[8]) & 0b00000111))), SEEK_CUR);
                }

                // Skip LZW minimum code size field
                fseek($stream, 1, SEEK_CUR);

                while ("\x00" !== $subBlockSize = fread($stream, 1)) {
                    if (1 !== \strlen($subBlockSize)) {
                        throw new InvalidImageContainerException('Invalid GIF sub block');
                    }

                    fseek($stream, \ord($subBlockSize), SEEK_CUR);
                }

                continue;
            }

            if ("\x21" !== $marker) {
                throw new InvalidImageContainerException('Invalid GIF block');
            }

            // Extension block
            $label = fread($stream, 1);

            if ("\xFE" === $label) {
                $block = '';

                while ("\x00" !== $subBlockSize = fread($stream, 1)) {
                    if (1 !== \strlen($subBlockSize)) {
                        throw new InvalidImageContainerException('Invalid GIF sub block');
                    }

                    $block .= $subBlockSize;
                    $block .= fread($stream, \ord($subBlockSize));
                }

                $metadata[] = [GifFormat::NAME => $this->parseFormat(GifFormat::NAME, $block)];

                continue;
            }

            $size = \ord(fread($stream, 1));
            $block = fread($stream, $size);

            if ("\xFF" !== $label || 'XMP DataXMP' !== $block) {
                while ("\x00" !== $subBlockSize = fread($stream, 1)) {
                    if (1 !== \strlen($subBlockSize)) {
                        throw new InvalidImageContainerException('Invalid GIF sub block');
                    }

                    fseek($stream, \ord($subBlockSize), SEEK_CUR);
                }

                continue;
            }

            $xmp = '';

            while ("\x00" !== $subBlockSize = fread($stream, 1)) {
                if (1 !== \strlen($subBlockSize)) {
                    throw new InvalidImageContainerException('Invalid GIF sub block');
                }

                // According to section 1.1.2 in the XMP Specification
                // Part 3, the size bytes are part of the UTF-8 text
                $xmp .= $subBlockSize;
                $xmp .= fread($stream, \ord($subBlockSize));
            }

            // Strip “magic” trailer
            if (false !== $trailerPos = strrpos($xmp, "\x01\xFF")) {
                $xmp = substr($xmp, 0, $trailerPos);
            }

            $metadata[] = [XmpFormat::NAME => $this->parseFormat(XmpFormat::NAME, $xmp)];
        }

        if (!$metadata) {
            return [];
        }

        return array_merge_recursive(...$metadata);
    }
}
