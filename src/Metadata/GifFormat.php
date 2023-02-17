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

class GifFormat extends AbstractFormat
{
    public const NAME = 'gif';
    public const DEFAULT_PRESERVE_KEYS = ['Comment'];

    public function serialize(ImageMetadata $metadata, array $preserveKeys): string
    {
        $gif = $metadata->getFormat(self::NAME);

        $gif['Comment'] = $this->filterValue(
            $metadata->getFormat(self::NAME)['Comment']
            ?? $metadata->getFormat(XmpFormat::NAME)['http://purl.org/dc/elements/1.1/']['rights']
            ?? $metadata->getFormat(IptcFormat::NAME)['2#116']
            ?? $metadata->getFormat(ExifFormat::NAME)['IFD0']['Copyright']
            ?? $metadata->getFormat(PngFormat::NAME)['Copyright']
            ?? $metadata->getFormat(XmpFormat::NAME)['http://ns.adobe.com/photoshop/1.0/']['Credit']
            ?? $metadata->getFormat(XmpFormat::NAME)['http://prismstandard.org/namespaces/prismusagerights/2.1/']['creditLine']
            ?? $metadata->getFormat(IptcFormat::NAME)['2#110']
            ?? $metadata->getFormat(PngFormat::NAME)['Disclaimer']
            ?? []
        );

        $filtered = [];

        foreach ($preserveKeys as $property) {
            $filtered[$property] = $this->filterValue($gif[$property] ?? []);
        }

        return $this->buildChunks($filtered);
    }

    public function parse(string $binaryChunk): array
    {
        $comment = '';

        while ('' !== $binaryChunk) {
            $subBlockSize = \ord($binaryChunk[0]);
            $comment .= substr($binaryChunk, 1, $subBlockSize);
            $binaryChunk = substr($binaryChunk, 1 + $subBlockSize);
        }

        return $this->toUtf8(['Comment' => [$comment]]);
    }

    private function buildChunks(array $metadata): string
    {
        $gifChunks = '';

        foreach ($metadata as $content) {
            foreach ($content as $item) {
                if (!$item) {
                    continue;
                }

                $gifChunks .= "\x21\xFE";

                while (\strlen($item) > 255) {
                    $gifChunks .= "\xFF".substr($item, 0, 255);
                    $item = substr($item, 255);
                }

                $gifChunks .= \chr(\strlen($item)).$item."\x00";
            }
        }

        return $gifChunks;
    }
}
