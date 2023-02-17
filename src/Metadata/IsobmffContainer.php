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

abstract class IsobmffContainer extends AbstractContainer
{
    /**
     * @var array
     */
    private $metadata = [];

    /**
     * @var array
     */
    private $items = [];

    /**
     * @var string
     */
    private $idat = '';

    public function apply($inputStream, $outputStream, ImageMetadata $metadata, array $preserveKeysByFormat): void
    {
        // TODO: implement writing
        stream_copy_to_stream($inputStream, $outputStream);
    }

    public function parse($stream): array
    {
        try {
            $this->parseBoxList($stream, PHP_INT_MAX);
            $this->parseItems($stream);

            return array_merge_recursive(...$this->metadata);
        } finally {
            $this->metadata = [];
            $this->items = [];
            $this->idat = '';
        }
    }

    private function parseBoxList($stream, int $length, string $path = ''): void
    {
        while ($length > 0 && false !== $head = fread($stream, 8)) {
            if ('' === $head) {
                break;
            }

            if (8 !== \strlen($head)) {
                throw new InvalidImageContainerException('Invalid ISOBMFF box');
            }
            $length -= 8;
            $size = unpack('N', substr($head, 0, 4))[1];
            $type = substr($head, 4);

            if (0 === $size) {
                $size = $length;
            }

            if (1 === $size) {
                $size = unpack('J', fread($stream, 8))[1] - 8;
                $length -= 8;
            }
            $size -= 8;
            $length -= $size;

            if ($size < 0 || $length < 0) {
                throw new InvalidImageContainerException('Invalid ISOBMFF box');
            }

            // TODO: support brotli-compressed boxes (brob)

            if (!\in_array($type, ['meta', 'iinf', 'Exif', 'xml ', 'iloc', 'infe', 'idat'], true)) {
                // Skip to the next box
                fseek($stream, $size, SEEK_CUR);

                continue;
            }

            $version = 0;

            // Full box
            if (\in_array($type, ['iinf', 'meta', 'iloc', 'infe'], true)) {
                $version = \ord(fread($stream, 1));
                fseek($stream, 3, SEEK_CUR); // Flags
                $size -= 4;
            }

            if ('iinf' === $type) {
                if ($version <= 1) {
                    fseek($stream, 2, SEEK_CUR); // 16bit count
                    $size -= 2;
                } else {
                    fseek($stream, 4, SEEK_CUR); // 32bit count
                    $size -= 4;
                }
            }

            if (\in_array($type, ['iinf', 'meta'], true)) {
                $this->parseBoxList($stream, $size, "$path/$type");
                continue;
            }

            $content = fread($stream, $size);

            if ('Exif' === $type && '' === $path) {
                $this->parseExif($content);
                continue;
            }

            if ('xml ' === $type && '' === $path) {
                $this->parseXmp($content);
                continue;
            }

            if ('idat' === $type && '/meta' === $path) {
                $this->idat = $content;
                continue;
            }

            if ('iloc' === $type && '/meta' === $path) {
                $this->parseIloc($content, $version);
                continue;
            }

            if ('infe' === $type && '/meta/iinf' === $path) {
                $this->parseInfe($content, $version);
            }
        }
    }

    private function parseIloc(string $content, int $version): void
    {
        $offsetSize = \ord($content[0]) >> 4;
        $lengthSize = \ord($content[0]) & 0x0F;
        $baseOffsetSize = \ord($content[1]) >> 4;
        $indexSize = $version ? \ord($content[1]) & 0x0F : 0;

        if ($version < 2) {
            $itemCount = unpack('n', substr($content, 2, 2))[1];
            $content = substr($content, 4);
        } else {
            $itemCount = unpack('N', substr($content, 2, 4))[1];
            $content = substr($content, 6);
        }

        for ($i = 0; $i < $itemCount; ++$i) {
            $itemId = unpack('n', substr($content, 0, 2))[1];
            $content = substr($content, 2);
            $method = 0;

            if ($version) {
                $method = unpack('n', substr($content, 0, 2))[1];
                $content = substr($content, 2);
            }
            $referenceIndex = unpack('n', substr($content, 0, 2))[1];
            $baseOffset = unpack('J', str_pad(substr($content, 2, $baseOffsetSize), 8, "\x00", STR_PAD_LEFT))[1];
            $content = substr($content, 2 + $baseOffsetSize);
            $extentCount = unpack('n', substr($content, 0, 2))[1];
            $content = substr($content, 2);

            if (0 === $referenceIndex) {
                $this->items[$itemId]['method'] = $method;
                $this->items[$itemId]['extents'] = [];
            }

            for ($j = 0; $j < $extentCount; ++$j) {
                $extentOffset = $baseOffset + unpack('J', str_pad(substr($content, 0, $offsetSize), 8, "\x00", STR_PAD_LEFT))[1];
                $content = substr($content, $offsetSize);

                if ($version) {
                    $content = substr($content, $indexSize); // Extent index
                }
                $extentLength = unpack('J', str_pad(substr($content, 0, $lengthSize), 8, "\x00", STR_PAD_LEFT))[1];
                $content = substr($content, $lengthSize);

                if (0 === $referenceIndex) {
                    $this->items[$itemId]['extents'][] = [$extentOffset, $extentLength];
                }
            }
        }
    }

    private function parseInfe(string $content, int $version): void
    {
        $itemID = unpack('n', substr($content, 0, 2))[1];
        //$itemProtectionIndex = unpack('n', substr($content, 2, 2))[1];
        $content = substr($content, 4);
        $itemType = '';

        if ($version > 1) {
            $itemType = substr($content, 0, 4);
            $content = substr($content, 4);
        }
        [$itemName, $contentType, $contentEncoding] = explode("\0", $content) + ['', '', ''];

        // Decoding not supported yet
        if ($contentEncoding) {
            return;
        }

        $this->items[$itemID]['type'] = $itemType;
        $this->items[$itemID]['name'] = $itemName;
        $this->items[$itemID]['contentType'] = $contentType;
        $this->items[$itemID]['encoding'] = $contentEncoding;
    }

    private function parseItems($stream): void
    {
        foreach ($this->items as $item) {
            if ('Exif' !== $item['type'] && 'XMP' !== $item['name'] && 'application/rdf+xml' !== $item['contentType']) {
                continue;
            }
            $content = '';

            if (0 === $item['method']) {
                foreach ($item['extents'] as $extent) {
                    fseek($stream, $extent[0]);
                    $content .= fread($stream, $extent[1]);
                }
            } elseif (1 === $item['method']) {
                foreach ($item['extents'] as $extent) {
                    fseek($stream, $extent[0]);
                    $content .= substr($this->idat, $extent[0], $extent[1]);
                }
            } else {
                continue;
            }

            if ('Exif' === $item['type']) {
                $this->parseExif($content);
            } else {
                $this->parseXmp($content);
            }
        }
    }

    private function parseExif(string $exif): void
    {
        $offset = unpack('N', substr($exif, 0, 4))[1];
        $start = substr($exif, $offset + 4, 4);

        // Skip to start of TIFF Header
        if ("II\x2A\x00" === $start || "MM\x00\x2A" === $start) {
            $exif = substr($exif, $offset + 4);
        }

        $this->metadata[] = [ExifFormat::NAME => $this->parseFormat(ExifFormat::NAME, $exif)];
    }

    private function parseXmp(string $xmp): void
    {
        $this->metadata[] = [XmpFormat::NAME => $this->parseFormat(XmpFormat::NAME, $xmp)];
    }
}
