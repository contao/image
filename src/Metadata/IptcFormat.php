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

class IptcFormat extends AbstractFormat
{
    public const NAME = 'iptc';
    public const DEFAULT_PRESERVE_KEYS = ['2#116', '2#080', '2#115', '2#110'];

    public function serialize(ImageMetadata $metadata, array $preserveKeys): string
    {
        $iptc = [];

        $iptc[116] = $this->filterValue(
            $metadata->getFormat(self::NAME)['2#116']
            ?? $metadata->getFormat(XmpFormat::NAME)['http://purl.org/dc/elements/1.1/']['rights']
            ?? $metadata->getFormat(ExifFormat::NAME)['Copyright']
            ?? $metadata->getFormat(PngFormat::NAME)['Copyright']
            ?? []
        );

        $iptc[80] = $this->filterValue(
            $metadata->getFormat(self::NAME)['2#080']
            ?? $metadata->getFormat(XmpFormat::NAME)['http://purl.org/dc/elements/1.1/']['creator']
            ?? $metadata->getFormat(ExifFormat::NAME)['Artist']
            ?? $metadata->getFormat(PngFormat::NAME)['Author']
            ?? []
        );

        $iptc[115] = $this->filterValue(
            $metadata->getFormat(self::NAME)['2#115']
            ?? $metadata->getFormat(XmpFormat::NAME)['http://ns.adobe.com/photoshop/1.0/']['Source']
            ?? $metadata->getFormat(PngFormat::NAME)['Source']
            ?? []
        );

        $iptc[110] = $this->filterValue(
            $metadata->getFormat(self::NAME)['2#110']
            ?? $metadata->getFormat(XmpFormat::NAME)['http://ns.adobe.com/photoshop/1.0/']['Credit']
            ?? $metadata->getFormat(PngFormat::NAME)['Disclaimer']
            ?? []
        );

        $iptc[5] = $this->filterValue(
            $metadata->getFormat(self::NAME)['2#005']
            ?? $metadata->getFormat(XmpFormat::NAME)['http://purl.org/dc/elements/1.1/']['title']
            ?? $metadata->getFormat(PngFormat::NAME)['Title']
            ?? []
        );

        $filtered = [];

        foreach ($preserveKeys as $property) {
            if (str_starts_with($property, '2#') && ($key = (int) substr($property, 2)) && isset($iptc[$key])) {
                $filtered[$key] = $iptc[$key];
            }
        }

        return $this->buildIptc($filtered);
    }

    public function parse(string $binaryChunk): array
    {
        $data = @iptcparse("Photoshop 3.0\x00$binaryChunk");

        if (!\is_array($data)) {
            return [];
        }

        return $this->toUtf8($data);
    }

    private function buildIptc(array $metadata): string
    {
        $iptc = "\x1C\x01".\chr(90); // 1:90 Coded Character Set
        $iptc .= pack('n', 3);
        $iptc .= "\x1B\x25\x47"; // UTF-8

        foreach ($metadata as $id => $values) {
            foreach ($values as $value) {
                if (!\is_string($value) || '' === $value) {
                    continue;
                }

                // TODO: handle maxlength

                $iptc .= "\x1C\x02".\chr($id);
                $iptc .= pack('n', \strlen($value));
                $iptc .= $value;
            }
        }

        if (\strlen($iptc) < 9) {
            return '';
        }

        // Image resource block
        $irb = '8BIM'; // Signature
        $irb .= "\x04\x04"; // IPTC-IIM Resource ID
        $irb .= "\x00\x00"; // Name
        $irb .= pack('N', \strlen($iptc)); // Size
        $irb .= $iptc;

        return $irb;
    }
}
