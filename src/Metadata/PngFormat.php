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

class PngFormat extends AbstractFormat
{
    public const NAME = 'png';
    public const DEFAULT_PRESERVE_KEYS = ['Copyright', 'Author', 'Source', 'Disclaimer'];

    public function serialize(ImageMetadata $metadata, array $preserveKeys): string
    {
        $png = $metadata->getFormat(self::NAME);

        $png['Copyright'] = $this->filterValue(
            $metadata->getFormat(self::NAME)['Copyright']
            ?? $metadata->getFormat(XmpFormat::NAME)['http://purl.org/dc/elements/1.1/']['rights']
            ?? $metadata->getFormat(IptcFormat::NAME)['2#116']
            ?? $metadata->getFormat(ExifFormat::NAME)['IFD0']['Copyright']
            ?? $metadata->getFormat(GifFormat::NAME)['Comment']
            ?? []
        );

        $png['Author'] = $this->filterValue(
            $metadata->getFormat(self::NAME)['Author']
            ?? $metadata->getFormat(XmpFormat::NAME)['http://purl.org/dc/elements/1.1/']['creator']
            ?? $metadata->getFormat(IptcFormat::NAME)['2#080']
            ?? $metadata->getFormat(ExifFormat::NAME)['IFD0']['Artist']
            ?? []
        );

        $png['Source'] = $this->filterValue(
            $metadata->getFormat(self::NAME)['Source']
            ?? $metadata->getFormat(XmpFormat::NAME)['http://ns.adobe.com/photoshop/1.0/']['Source']
            ?? $metadata->getFormat(IptcFormat::NAME)['2#115']
            ?? []
        );

        $png['Disclaimer'] = $this->filterValue(
            $metadata->getFormat(self::NAME)['Disclaimer']
            ?? $metadata->getFormat(XmpFormat::NAME)['http://ns.adobe.com/photoshop/1.0/']['Credit']
            ?? $metadata->getFormat(IptcFormat::NAME)['2#110']
            ?? []
        );

        $png['Title'] = $this->filterValue(
            $metadata->getFormat(self::NAME)['Title']
            ?? $metadata->getFormat(XmpFormat::NAME)['http://purl.org/dc/elements/1.1/']['title']
            ?? $metadata->getFormat(IptcFormat::NAME)['2#005']
            ?? []
        );

        $filtered = [];

        foreach ($preserveKeys as $property) {
            $filtered[$property] = $this->filterValue($png[$property] ?? []);
        }

        return $this->buildChunks($filtered);
    }

    public function parse(string $binaryChunk): array
    {
        [$keyword, $text] = explode("\x00", $binaryChunk, 2) + ['', ''];

        return $this->toUtf8([$keyword => [$text]]);
    }

    private function buildChunks(array $metadata): string
    {
        $pngChunks = '';

        foreach ($metadata as $keyword => $content) {
            $content = implode(', ', $content);

            if (!$content) {
                continue;
            }

            $data = $this->buildItxt($keyword, $content);
            $length = pack('N', \strlen($data) - 4);
            $crc = hash('crc32b', $data, true);
            $pngChunks .= "$length$data$crc";
        }

        return $pngChunks;
    }

    private function buildItxt(string $keyword, string $content): string
    {
        $compressed = gzcompress($content, 9, ZLIB_ENCODING_DEFLATE);

        if (\strlen($compressed) < \strlen($content)) {
            return "iTXt$keyword\x00\x01\x00\x00\x00$compressed";
        }

        return "iTXt$keyword\x00\x00\x00\x00\x00$content";
    }
}
