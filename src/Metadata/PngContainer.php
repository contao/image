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

            if ('iTXt' === $type) {
                $metadata[] = $this->parseItxt(fread($stream, $size));
                fseek($stream, 4, SEEK_CUR);
                continue;
            }

            // TODO: eXIf http://ftp-osl.osuosl.org/pub/libpng/documents/pngext-1.5.0.html#C.eXIf

            // TODO: tEXt "Raw profile type iptc": https://github.com/exiftool/exiftool/blob/3de0f8ce75c7916567e6354a837faefea207730b/lib/Image/ExifTool/WritePNG.pl#L96-L99
            // $data = sprintf("\nIPTC profile\n%8d\n", strlen($data)).bin2hex($data);

            // Skip to the next chunk
            fseek($stream, $size + 4, SEEK_CUR);
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

    private function parseItxt(string $itxt): array
    {
        $keyword = substr($itxt, 0, strpos($itxt, "\x00"));
        $compressionFlag = "\x00" !== $itxt[\strlen($keyword) + 1];
        $text = substr($itxt, strpos($itxt, "\x00", strpos($itxt, "\x00", \strlen($keyword) + 3) + 1) + 1);

        if ('XML:com.adobe.xmp' !== $keyword) {
            return [];
        }

        if ($compressionFlag) {
            $text = gzuncompress($text);
        }

        return [XmpFormat::NAME => $this->parser->parseFormat(XmpFormat::NAME, $text)];
    }
}
