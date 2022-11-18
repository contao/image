<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image;

use Contao\Image\Exception\RuntimeException;

final class ImageMetadata
{
    private int $byteSize = 0;
    private array $xmp = [];
    private array $iptc = [];
    private array $exif = [];

    public static function fromPath(string $path): static
    {
        if (!$stream = @fopen($path, 'r')) {
            return new self();
        }

        return self::fromStream($stream);
    }

    /**
     * @param resource $stream
     */
    public static function fromStream($stream): static
    {
        if (!\is_resource($stream) || 'stream' !== get_resource_type($stream)) {
            throw new \TypeError(sprintf('Argument 1 passed to %s() must be of the type resource, %s given', __METHOD__, get_debug_type($stream)));
        }

        $metadata = new self();
        $metadata->parseStream($stream);

        return $metadata;
    }

    public function getCopyright(): string
    {
        return implode(
            ', ',
            (array) (
                $this->xmp['http://purl.org/dc/elements/1.1/']['rights']
                ?? $this->iptc['2#116']
                ?? $this->exif['IFD0']['Copyright']
                ?? $this->xmp['http://purl.org/dc/elements/1.1/']['creator']
                ?? $this->iptc['2#080']
                ?? $this->exif['IFD0']['Artist']
                ?? $this->iptc['2#110']
                ?? []
            ),
        );
    }

    public function getCreator(): string
    {
        return implode(
            ', ',
            (array) (
                $this->xmp['http://purl.org/dc/elements/1.1/']['creator']
                ?? $this->iptc['2#080']
                ?? $this->exif['IFD0']['Artist']
                ?? []
            ),
        );
    }

    public function getSource(): string
    {
        return implode(
            ', ',
            (array) (
                $this->xmp['http://ns.adobe.com/photoshop/1.0/']['GettyImagesGIFT:AssetID']
                ?? $this->xmp['http://ns.adobe.com/photoshop/1.0/']['Source']
                ?? $this->iptc['2#115']
                ?? $this->iptc['2#005']
                ?? []
            ),
        );
    }

    public function getCredit(): string
    {
        return implode(
            ', ',
            (array) (
                $this->xmp['http://ns.adobe.com/photoshop/1.0/']['Credit']
                ?? $this->iptc['2#110']
                ?? $this->xmp['http://prismstandard.org/namespaces/prismusagerights/2.1/']['creditLine']
                ?? []
            ),
        );
    }

    public function getRawData(): array
    {
        return [
            'xmp' => $this->xmp,
            'iptc' => $this->iptc,
            'exif' => $this->exif,
        ];
    }

    public function getByteSize(): int
    {
        return $this->byteSize;
    }

    /**
     * @param resource $stream
     */
    private function parseStream($stream): void
    {
        $type = fread($stream, 3);

        // Rewind
        fseek($stream, -3, SEEK_CUR);

        match ($type) {
            "\xFF\xD8\xFF" => $this->parseJpeg($stream),
            "\x89\x50\x4E" => $this->parsePng($stream),
            "\x52\x49\x46" => $this->parseWebp($stream),
            default => null,
        };
    }

    /**
     * @param resource $stream
     */
    private function parseJpeg($stream): void
    {
        while (false !== $marker = fread($stream, 2)) {
            if (2 !== \strlen($marker) || "\xFF" !== $marker[0]) {
                throw new RuntimeException('Invalid JPEG marker');
            }

            // Skip two byte markers
            if (\ord($marker[1]) >= 0xD0 && \ord($marker[1]) <= 0xD9) {
                continue;
            }

            // Start of scan marker
            if ("\xDA" === $marker[1]) {
                return;
            }

            $size = unpack('n', fread($stream, 2))[1];

            if ("\xE1" === $marker[1]) {
                $this->parseJpegApp1(fread($stream, $size - 2));
                continue;
            }

            if ("\xED" === $marker[1]) {
                $this->parseJpegApp13(fread($stream, $size - 2));
                continue;
            }

            // Skip to the next marker
            fseek($stream, $size - 2, SEEK_CUR);
        }
    }

    /**
     * @param resource $stream
     */
    private function parsePng($stream): void
    {
        if ("\x89\x50\x4E\x47\x0D\x0A\x1A\x0A" !== fread($stream, 8)) {
            throw new RuntimeException('Invalid PNG');
        }

        while (false !== $marker = fread($stream, 8)) {
            if (8 !== \strlen($marker)) {
                throw new RuntimeException('Invalid PNG chunk');
            }

            $size = unpack('N', substr($marker, 0, 4))[1];
            $type = substr($marker, 4, 4);

            if ('iTXt' === $type) {
                $this->parsePngItxt(fread($stream, $size));
                fseek($stream, 4, SEEK_CUR);
                continue;
            }

            // Skip to the next chunk
            fseek($stream, $size + 4, SEEK_CUR);
        }
    }

    /**
     * @param resource $stream
     */
    private function parseWebp($stream): void
    {
        $head = fread($stream, 12);

        if (!str_starts_with($head, 'RIFF') || 'WEBP' !== substr($head, 8)) {
            throw new RuntimeException('Invalid WEBP');
        }

        while (false !== $marker = fread($stream, 8)) {
            if (8 !== \strlen($marker)) {
                throw new RuntimeException(sprintf('Invalid chunk at offset %s', ftell($stream) - 2));
            }

            $type = substr($marker, 0, 4);
            $size = unpack('V', substr($marker, 4, 4))[1];

            if ('EXIF' === $type) {
                $this->parseExif(fread($stream, $size));
                continue;
            }

            if ('XMP ' === $type) {
                $this->parseXmp(fread($stream, $size));
                continue;
            }

            // Skip to the next chunk
            fseek($stream, $size, SEEK_CUR);
        }
    }

    private function parseJpegApp1(string $app1): void
    {
        if (str_starts_with($app1, "Exif\x00\x00")) {
            $this->parseExif(substr($app1, 6));
        } elseif (str_starts_with($app1, "http://ns.adobe.com/xap/1.0/\x00")) {
            $this->parseXmp(substr($app1, 29));
        }
    }

    private function parseJpegApp13(string $app13): void
    {
        if (str_starts_with($app13, "Photoshop 3.0\x00")) {
            $this->parseIptc(substr($app13, 14));
        } elseif (str_starts_with($app13, 'Adobe_Photoshop2.5:')) {
            $this->parseIptc(substr($app13, 19));
        }
    }

    private function parsePngItxt(string $itxt): void
    {
        $keyword = substr($itxt, 0, strpos($itxt, "\x00"));
        $compressionFlag = "\x00" !== $itxt[\strlen($keyword) + 1];
        $text = substr($itxt, strpos($itxt, "\x00", strpos($itxt, "\x00", \strlen($keyword) + 3) + 1) + 1);

        if ('XML:com.adobe.xmp' !== $keyword) {
            return;
        }

        if ($compressionFlag) {
            // TODO
        }

        $this->parseXmp($text);
    }

    private function parseExif(string $exif): void
    {
        $app1 = "Exif\x00\x00$exif";

        $jpegStream = fopen('php://memory', 'r+');

        fwrite($jpegStream, "\xFF\xD8\xFF\xE1");
        fwrite($jpegStream, pack('n', \strlen($app1) + 2));
        fwrite($jpegStream, $app1);
        fwrite($jpegStream, "\xFF\xDA\x00\x02\xFF\xD9");

        rewind($jpegStream);

        $data = @exif_read_data($jpegStream, '', true);

        if (!\is_array($data)) {
            return;
        }

        $this->byteSize += \strlen($exif);
        $this->exif = $data;
    }

    private function parseIptc(string $resourceDataBlocks): void
    {
        $data = @iptcparse("Photoshop 3.0\x00$resourceDataBlocks");

        if (!\is_array($data)) {
            return;
        }

        $this->byteSize += \strlen($resourceDataBlocks);
        $this->iptc = $data;
    }

    private function parseXmp(string $xmpPacket): void
    {
        $dom = new \DOMDocument();
        $dom->loadXML($xmpPacket);

        foreach ($dom->documentElement->childNodes as $rdf) {
            if ('RDF' !== $rdf->localName || 'http://www.w3.org/1999/02/22-rdf-syntax-ns#' !== $rdf->namespaceURI) {
                continue;
            }

            foreach ($rdf->childNodes ?? [] as $desc) {
                if ('Description' !== $desc->localName || 'http://www.w3.org/1999/02/22-rdf-syntax-ns#' !== $desc->namespaceURI) {
                    continue;
                }

                foreach ($desc->attributes ?? [] as $attr) {
                    $this->setXmpValue($attr->namespaceURI, $attr->localName, $attr->value);
                }

                foreach ($desc->childNodes ?? [] as $node) {
                    if ($node instanceof \DOMElement && $node->firstElementChild) {
                        $this->setXmpValue($node->namespaceURI, $node->localName, $node->firstElementChild);
                    }
                }
            }
        }

        $this->byteSize += \strlen($xmpPacket);
    }

    private function setXmpValue(string $namespace, string $attr, string|\DOMElement $value): void
    {
        if ($value instanceof \DOMElement) {
            if ($value->firstElementChild) {
                $values = [];

                foreach ($value->getElementsByTagNameNS('http://www.w3.org/1999/02/22-rdf-syntax-ns#', 'li') as $valueNode) {
                    $values[] = $valueNode->textContent;
                }
            } else {
                $values = [$value->textContent];
            }
        } else {
            $values = [$value];
        }

        $this->xmp[$namespace][$attr] = array_values(array_unique(array_filter($values)));
    }
}
