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

final class MetadataParser
{
    private int $byteSize;
    private array $xmp;
    private array $iptc;
    private array $exif;

    /**
     * @param resource|string $pathOrStream
     */
    public function parse($pathOrStream): ImageMetadata
    {
        if (!\is_string($pathOrStream) && (!\is_resource($pathOrStream) || 'stream' !== get_resource_type($pathOrStream))) {
            throw new \TypeError(sprintf('Argument 1 passed to %s() must be of the type resource|string, %s given', __METHOD__, get_debug_type($pathOrStream)));
        }

        if (\is_string($pathOrStream)) {
            $stream = @fopen($pathOrStream, 'r');

            if (!$stream) {
                return new ImageMetadata([], [], [], 0);
            }
        } else {
            $stream = $pathOrStream;
        }

        $this->byteSize = 0;
        $this->xmp = [];
        $this->iptc = [];
        $this->exif = [];

        $type = fread($stream, 3);

        // TODO: not supported by all streams
        // Rewind
        fseek($stream, -3, SEEK_CUR);

        match ($type) {
            "\xFF\xD8\xFF" => $this->parseJpeg($stream),
            "\x89\x50\x4E" => $this->parsePng($stream),
            "\x52\x49\x46" => $this->parseWebp($stream),
            default => null,
        };

        return new ImageMetadata($this->xmp, $this->iptc, $this->exif, $this->byteSize);
    }

    public function applyCopyrightToFile(ImageMetadata $metadata, string $inputPath, string $outputPath): void
    {
        $input = fopen($inputPath, 'r');

        if (!$input) {
            throw new RuntimeException(sprintf('Unable to open image path "%s"', $inputPath));
        }

        $output = fopen($outputPath, 'w');

        if (!$output) {
            throw new RuntimeException(sprintf('Unable to write image to path "%s"', $outputPath));
        }

        $this->applyCopyrightToStream($metadata, $input, $output);
    }

    /**
     * @param resource $inputStream
     * @param resource $outputStream
     */
    public function applyCopyrightToStream(ImageMetadata $metadata, $inputStream, $outputStream): void
    {
        if (!\is_resource($inputStream) || 'stream' !== get_resource_type($inputStream)) {
            throw new \TypeError(sprintf('Argument 2 passed to %s() must be of the type resource, %s given', __METHOD__, get_debug_type($inputStream)));
        }

        if (!\is_resource($outputStream) || 'stream' !== get_resource_type($outputStream)) {
            throw new \TypeError(sprintf('Argument 3 passed to %s() must be of the type resource, %s given', __METHOD__, get_debug_type($outputStream)));
        }

        // Empty metadata
        if (!$metadata->getCopyright() && !$metadata->getCreator() && !$metadata->getSource() && !$metadata->getCredit()) {
            stream_copy_to_stream($inputStream, $outputStream);

            return;
        }

        $xmp = $this->buildXmp($metadata);
        $iptc = $this->buildIptc($metadata);
        $exif = $this->buildExif($metadata);

        $type = fread($inputStream, 3);

        // TODO: not supported by all streams
        // Rewind
        fseek($inputStream, -3, SEEK_CUR);

        match ($type) {
            "\xFF\xD8\xFF" => $this->applyToJpeg($inputStream, $outputStream, $xmp, $iptc, $exif),
            "\x89\x50\x4E" => $this->applyToPng($inputStream, $outputStream, $xmp, $iptc, $exif),
            "\x52\x49\x46" => $this->applyToWebp($inputStream, $outputStream, $xmp, $iptc, $exif),
            default => null,
        };
    }

    private function applyToJpeg($inputStream, $outputStream, $xmp, $iptc, $exif): void
    {
        while (false !== $marker = fread($inputStream, 2)) {
            if (2 !== \strlen($marker) || "\xFF" !== $marker[0]) {
                throw new RuntimeException('Invalid JPEG marker');
            }

            // Start of scan marker
            if ("\xDA" === $marker[1]) {
                fwrite($outputStream, $this->buildJpegMarkerSegment("\xE1", "http://ns.adobe.com/xap/1.0/\x00$xmp"));
                fwrite($outputStream, $this->buildJpegMarkerSegment("\xE1", "Exif\x00\x00$exif"));
                fwrite($outputStream, $this->buildJpegMarkerSegment("\xED", "Photoshop 3.0\x00$iptc"));

                // Copy the rest of the image
                fwrite($outputStream, $marker);
                stream_copy_to_stream($inputStream, $outputStream);

                return;
            }

            fwrite($outputStream, $marker);

            // Skip two byte markers
            if (\ord($marker[1]) >= 0xD0 && \ord($marker[1]) <= 0xD9) {
                continue;
            }

            $sizeBytes = fread($inputStream, 2);
            fwrite($outputStream, $sizeBytes);

            $size = unpack('n', $sizeBytes)[1];

            stream_copy_to_stream($inputStream, $outputStream, $size - 2);
        }
    }

    private function buildJpegMarkerSegment($marker, $content): string
    {
        $size = pack('n', \strlen($content) + 2);

        return "\xFF$marker$size$content";
    }

    private function buildXmp(ImageMetadata $metadata): string
    {
        $dom = new \DOMDocument();
        $dom->loadXML(
            '<?xpacket begin="" id="W5M0MpCehiHzreSzNTczkc9d"?>'
            .'<x:xmpmeta xmlns:x="adobe:ns:meta/">'
            .'<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">'
            .'<rdf:Description/>'
            .'</rdf:RDF>'
            .'</x:xmpmeta>'
            .'<?xpacket end="w"?>'
        );

        /** @var \DOMElement $description */
        $description = $dom->documentElement->firstChild->firstChild;

        if ($metadata->getCopyright()) {
            $description->setAttributeNS('http://purl.org/dc/elements/1.1/', 'dc:rights', $metadata->getCopyright());
        }

        if ($metadata->getCreator()) {
            $description->setAttributeNS('http://purl.org/dc/elements/1.1/', 'dc:creator', $metadata->getCreator());
        }

        if ($metadata->getSource()) {
            $description->setAttributeNS('http://ns.adobe.com/photoshop/1.0/', 'photoshop:Source', $metadata->getSource());
        }

        if ($metadata->getCredit()) {
            $description->setAttributeNS('http://ns.adobe.com/photoshop/1.0/', 'photoshop:Credit', $metadata->getCredit());
        }

        return $dom->saveXML();
    }

    private function buildIptc(ImageMetadata $metadata): string
    {
        $iptc = '';

        if ($metadata->getCopyright()) {
            // Copyright 2:116
            $iptc .= "\x1C\x02\x74";
            $iptc .= pack('n', \strlen($metadata->getCopyright()));
            $iptc .= $metadata->getCopyright();
        }

        if ($metadata->getCreator()) {
            // By-line 2:80
            $iptc .= "\x1C\x02\x50";
            $iptc .= pack('n', \strlen($metadata->getCreator()));
            $iptc .= $metadata->getCreator();
        }

        if ($metadata->getSource()) {
            // Source 2:115
            $iptc .= "\x1C\x02\x73";
            $iptc .= pack('n', \strlen($metadata->getSource()));
            $iptc .= $metadata->getSource();
        }

        if ($metadata->getCredit()) {
            // Credit 2:110
            $iptc .= "\x1C\x02\x6E";
            $iptc .= pack('n', \strlen($metadata->getCredit()));
            $iptc .= $metadata->getCredit();
        }

        // Image resource block
        $irb = '8BIM'; // Signature
        $irb .= "\x04\x04"; // IPTC-IIM Resource ID
        $irb .= "\x00\x00"; // Name
        $irb .= pack('N', \strlen($iptc)); // Size
        $irb .= $iptc;

        return $irb;
    }

    private function buildExif(ImageMetadata $metadata)
    {
        $copyright = $metadata->getCopyright() ?: $metadata->getCredit() ?: ' ';
        $artist = $metadata->getCreator() ?: $metadata->getSource() ?: ' ';

        // Offset to data area
        $offset = 38;

        $exif = "II\x2A\x00"; // TIFF header Intel byte order (little endian)
        $exif .= pack('V', 8); // Offset to first IFD
        $exif .= pack('v', 2); // Number of directory entries

        $exif .= "\x98\x82"; // Copyright 0x8298
        $exif .= "\x02\x00"; // ASCII string
        $exif .= pack('V', \strlen($copyright)); // String size

        if (\strlen($copyright) > 4) {
            $exif .= pack('V', $offset); // Offset to data value
            $offset += \strlen($copyright);
        } else {
            $exif .= str_pad($copyright, 4, "\x00"); // 4 byte string
        }

        $exif .= "\x3B\x01"; // Artist 0x013B
        $exif .= "\x02\x00"; // ASCII string
        $exif .= pack('V', \strlen($artist)); // String size

        if (\strlen($artist) > 4) {
            $exif .= pack('V', $offset); // Offset to data value
        } else {
            $exif .= str_pad($artist, 4, "\x00"); // 4 byte string
        }

        $exif .= "\x00\x00\x00\x00"; // Last IFD

        if (\strlen($copyright) > 4) {
            $exif .= $copyright; // Data area
        }

        if (\strlen($artist) > 4) {
            $exif .= $artist; // Data area
        }

        return $exif;
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

        foreach ($dom->getElementsByTagNameNS('http://www.w3.org/1999/02/22-rdf-syntax-ns#', 'RDF') as $rdf) {
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
