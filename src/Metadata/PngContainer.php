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

class PngContainer extends AbstractContainer
{
    public function __construct(private MetadataParser $parser)
    {
    }

    public function getMagicBytes(): string
    {
        return "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A";
    }

    public function apply($inputStream, $outputStream, ImageMetadata $metadata): void
    {
        $xmp = $this->parser->serializeFormat(XmpFormat::NAME, $metadata);
        $iptc = $this->parser->serializeFormat(IptcFormat::NAME, $metadata);
        $exif = $this->parser->serializeFormat(ExifFormat::NAME, $metadata);

        $head = fread($inputStream, 8);

        if ($head !== $this->getMagicBytes()) {
            throw new RuntimeException('Invalid PNG');
        }

        fwrite($outputStream, $head);

        while (false !== $marker = fread($inputStream, 8)) {
            if ('' === $marker) {
                break;
            }

            if (8 !== \strlen($marker)) {
                throw new RuntimeException(sprintf('Invalid chunk "%s"', '0x'.bin2hex($marker)));
            }

            $size = unpack('N', substr($marker, 0, 4))[1];
            $type = substr($marker, 4, 4);

            if ('IDAT' === $type) {
                fwrite($outputStream, $this->buildItxt('XML:com.adobe.xmp', $xmp));
                fwrite($outputStream, $this->buildChunk('eXIf', $exif));
                // Non-standard exiftool/exiv2 format
                fwrite($outputStream, $this->buildItxt('Raw profile type iptc', sprintf("\nIPTC profile\n%8d\n%s", \strlen($iptc), bin2hex($iptc))));
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
            throw new RuntimeException('Invalid PNG');
        }

        while (false !== $marker = fread($stream, 8)) {
            if ('' === $marker) {
                break;
            }

            if (8 !== \strlen($marker)) {
                throw new RuntimeException(sprintf('Invalid chunk "%s"', '0x'.bin2hex($marker)));
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

        return array_merge(...$metadata);
    }

    private function buildItxt(string $keyword, string $content): string
    {
        $content = gzcompress($content, 9, ZLIB_ENCODING_DEFLATE);

        return $this->buildChunk(
            'iTXt',
            "$keyword\x00\x01\x00\x00\x00$content",
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
        return [ExifFormat::NAME => $this->parser->parseFormat(ExifFormat::NAME, $exif)];
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
            return [XmpFormat::NAME => $this->parser->parseFormat(XmpFormat::NAME, $text)];
        }

        // Non-standard exiftool/exiv2 format
        if ('Raw profile type iptc' === $keyword) {
            $chunks = explode("\n", trim($text));

            if (
                3 === \count($chunks)
                && 'IPTC profile' === $chunks[0]
                && ($length = (int) $chunks[1])
                && ($iptc = hex2bin($chunks[2]))
                && \strlen($iptc) === $length
            ) {
                return [IptcFormat::NAME => $this->parser->parseFormat(IptcFormat::NAME, $iptc)];
            }
        }

        return [];
    }
}
