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

class PngContainer extends AbstractContainer
{
    public function getMagicBytes(): string
    {
        return "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A";
    }

    public function apply($inputStream, $outputStream, ImageMetadata $metadata, array $preserveKeysByFormat): void
    {
        $png = $this->metadataReaderWriter->serializeFormat(PngFormat::NAME, $metadata, $preserveKeysByFormat[PngFormat::NAME] ?? []);
        $xmp = $this->metadataReaderWriter->serializeFormat(XmpFormat::NAME, $metadata, $preserveKeysByFormat[XmpFormat::NAME] ?? []);
        $iptc = $this->metadataReaderWriter->serializeFormat(IptcFormat::NAME, $metadata, $preserveKeysByFormat[IptcFormat::NAME] ?? []);
        $exif = $this->metadataReaderWriter->serializeFormat(ExifFormat::NAME, $metadata, $preserveKeysByFormat[ExifFormat::NAME] ?? []);

        $head = fread($inputStream, 8);

        if ($head !== $this->getMagicBytes()) {
            throw new InvalidImageContainerException('Invalid PNG head');
        }

        fwrite($outputStream, $head);

        while (false !== $marker = fread($inputStream, 8)) {
            if ('' === $marker) {
                break;
            }

            if (8 !== \strlen($marker)) {
                throw new InvalidImageContainerException(sprintf('Invalid PNG chunk "%s"', '0x'.bin2hex($marker)));
            }

            $size = unpack('N', substr($marker, 0, 4))[1];
            $type = substr($marker, 4, 4);

            if ('IDAT' === $type) {
                fwrite($outputStream, $png);

                if ($xmp) {
                    fwrite($outputStream, $this->buildItxt('XML:com.adobe.xmp', $xmp));
                }

                if ($exif) {
                    fwrite($outputStream, $this->buildChunk('eXIf', $exif));
                }

                if ($iptc) {
                    // Non-standard ImageMagick/exiftool/exiv2 format
                    fwrite($outputStream, $this->buildItxt('Raw profile type iptc', sprintf("\nIPTC profile\n%8d\n%s", \strlen($iptc), bin2hex($iptc))));
                }

                // Copy the rest of the image
                fwrite($outputStream, $marker);
                stream_copy_to_stream($inputStream, $outputStream);

                return;
            }

            fwrite($outputStream, $marker);
            stream_copy_to_stream($inputStream, $outputStream, $size + 4);
        }
    }

    public function parse($stream): array
    {
        $metadata = [];

        $head = fread($stream, 8);

        if ($head !== $this->getMagicBytes()) {
            throw new InvalidImageContainerException('Invalid PNG head');
        }

        while (false !== $marker = fread($stream, 8)) {
            if ('' === $marker) {
                break;
            }

            if (8 !== \strlen($marker)) {
                throw new InvalidImageContainerException(sprintf('Invalid PNG chunk "%s"', '0x'.bin2hex($marker)));
            }

            $size = unpack('N', substr($marker, 0, 4))[1];
            $type = substr($marker, 4, 4);

            // Skip to the next chunk
            if (!\in_array($type, ['tEXt', 'zTXt', 'iTXt', 'eXIf'], true)) {
                fseek($stream, $size + 4, SEEK_CUR);
                continue;
            }

            $data = fread($stream, $size);

            if ('tEXt' === $type) {
                $metadata[] = $this->parseText($data);
            } elseif ('zTXt' === $type) {
                $metadata[] = $this->parseZtxt($data);
            } elseif ('iTXt' === $type) {
                $metadata[] = $this->parseItxt($data);
            } elseif ('eXIf' === $type) {
                $metadata[] = $this->parseExif($data);
            }

            // Skip CRC value
            fseek($stream, 4, SEEK_CUR);
        }

        if (!$metadata) {
            return [];
        }

        return array_merge_recursive(...$metadata);
    }

    private function buildItxt(string $keyword, string $content): string
    {
        $compressed = gzcompress($content, 9, ZLIB_ENCODING_DEFLATE);

        if (\strlen($compressed) < \strlen($content)) {
            return $this->buildChunk(
                'iTXt',
                "$keyword\x00\x01\x00\x00\x00$compressed"
            );
        }

        return $this->buildChunk(
            'iTXt',
            "$keyword\x00\x00\x00\x00\x00$content"
        );
    }

    private function buildChunk(string $type, string $content): string
    {
        $length = pack('N', \strlen($content));
        $data = pack('A4', $type).$content;
        $crc = hash('crc32b', $data, true);

        return "$length$data$crc";
    }

    private function parseExif(string $exif): array
    {
        return [ExifFormat::NAME => $this->parseFormat(ExifFormat::NAME, $exif)];
    }

    private function parseText(string $text): array
    {
        [$keyword, $text] = explode("\x00", $text, 2) + ['', ''];

        return $this->parseTextualData($keyword, $text);
    }

    private function parseZtxt(string $ztxt): array
    {
        [$keyword, $text] = explode("\x00\x00", $ztxt, 2) + ['', ''];

        return $this->parseTextualData($keyword, gzuncompress($text));
    }

    private function parseItxt(string $itxt): array
    {
        $keyword = substr($itxt, 0, strpos($itxt, "\x00"));
        $compressionFlag = "\x00" !== $itxt[\strlen($keyword) + 1];
        $text = substr($itxt, strpos($itxt, "\x00", strpos($itxt, "\x00", \strlen($keyword) + 3) + 1) + 1);

        if ($compressionFlag) {
            $text = gzuncompress($text);
        }

        return $this->parseTextualData($keyword, $text);
    }

    private function parseTextualData(string $keyword, string $text): array
    {
        if ('XML:com.adobe.xmp' === $keyword) {
            return [XmpFormat::NAME => $this->parseFormat(XmpFormat::NAME, $text)];
        }

        // Non-standard ImageMagick/exiftool/exiv2 format
        if (str_starts_with($keyword, 'Raw profile type ') && \in_array(substr($keyword, 17), ['iptc', 'exif', 'APP1'], true)) {
            $chunks = explode("\n", trim($text), 3);

            if (
                3 === \count($chunks)
                && ($length = (int) $chunks[1])
                && ($profile = hex2bin(preg_replace('/\s+/', '', $chunks[2])))
                && \strlen($profile) === $length
            ) {
                if ('Raw profile type iptc' === $keyword) {
                    return [IptcFormat::NAME => $this->parseFormat(IptcFormat::NAME, $profile)];
                }

                if (str_starts_with($profile, "Exif\x00\x00")) {
                    return [ExifFormat::NAME => $this->parseFormat(ExifFormat::NAME, substr($profile, 6))];
                }

                if (str_starts_with($profile, "http://ns.adobe.com/xap/1.0/\x00")) {
                    return [XmpFormat::NAME => $this->parseFormat(XmpFormat::NAME, substr($profile, 29))];
                }

                return [ExifFormat::NAME => $this->parseFormat(ExifFormat::NAME, $profile)];
            }
        }

        return [PngFormat::NAME => $this->parseFormat(PngFormat::NAME, "$keyword\x00$text")];
    }
}
