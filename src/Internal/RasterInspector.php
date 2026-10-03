<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image\Internal;

use Contao\Image\Dimensions;
use Contao\Image\ImageInformation;
use Contao\Image\Size;

final class RasterInspector
{
    public function jpeg(InspectionStream $stream): ?ImageInformation
    {
        $stream->skip(2);
        $width = 0;
        $height = 0;
        $orientation = 1;

        while (null !== ($type = $this->jpegMarker($stream))) {
            if (\in_array($type, [0xDA, 0xD9], true)) {
                break;
            }

            if ($type >= 0xD0 && $type <= 0xD8) {
                continue;
            }
            $length = $stream->read(2);

            if (2 !== \strlen($length)) {
                return null;
            }
            $length = unpack('n', $length)[1] - 2;

            if ($length < 0) {
                return null;
            }
            $data = $stream->read($length);

            if (\strlen($data) !== $length) {
                return null;
            }

            if (0xE1 === $type && str_starts_with($data, "Exif\0\0")) {
                $orientation = OrientationReader::read($data);
            }

            if (\in_array($type, [0xC0, 0xC1, 0xC2, 0xC3, 0xC5, 0xC6, 0xC7, 0xC9, 0xCA, 0xCB, 0xCD, 0xCE, 0xCF], true) && \strlen($data) >= 5) {
                $height = unpack('n', substr($data, 1, 2))[1];
                $width = unpack('n', substr($data, 3, 2))[1];
            }
        }

        return $this->information($width, $height, ['jpg', $orientation]);
    }

    public function png(InspectionStream $stream): ?ImageInformation
    {
        $stream->skip(8);
        $width = 0;
        $height = 0;
        $orientation = 1;

        while (8 === \strlen($header = $stream->read(8))) {
            $length = unpack('N', substr($header, 0, 4))[1];
            $type = substr($header, 4, 4);

            if ('IHDR' === $type && 13 === $length) {
                $data = $stream->read($length);

                if (13 !== \strlen($data)) {
                    return null;
                }
                $width = unpack('N', substr($data, 0, 4))[1];
                $height = unpack('N', substr($data, 4, 4))[1];
            } elseif ('eXIf' === $type) {
                $orientation = OrientationReader::read($stream->read($length));
            } else {
                $stream->skip($length);
            }
            $stream->skip(4);
            // EXIF can follow the image payload, so continue through IEND
            if ('IEND' === $type) {
                return $this->information($width, $height, ['png', $orientation]);
            }
        }

        return null;
    }

    public function gif(InspectionStream $stream): ?ImageInformation
    {
        $data = $stream->read(10);

        if (10 !== \strlen($data)) {
            return null;
        }

        return $this->information(unpack('v', substr($data, 6, 2))[1], unpack('v', substr($data, 8, 2))[1], ['gif', 1]);
    }

    public function webp(InspectionStream $stream): ?ImageInformation
    {
        $stream->skip(12);
        $width = 0;
        $height = 0;
        $orientation = 1;
        $hasExif = false;

        while (8 === \strlen($header = $stream->read(8))) {
            $length = unpack('V', substr($header, 4, 4))[1];
            $type = substr($header, 0, 4);
            $data = $stream->read(min($length, 'EXIF' === $type ? $length : 10));

            if ('VP8X' === $type && 10 === \strlen($data)) {
                $hasExif = 0 !== (\ord($data[0]) & 8);
                $width = unpack('V', substr($data, 4, 3)."\0")[1] + 1;
                $height = unpack('V', substr($data, 7, 3)."\0")[1] + 1;
            } elseif ('VP8 ' === $type && 10 === \strlen($data)) {
                $width = unpack('v', substr($data, 6, 2))[1] & 0x3FFF;
                $height = unpack('v', substr($data, 8, 2))[1] & 0x3FFF;
            } elseif ('VP8L' === $type && \strlen($data) >= 5) {
                $bits = unpack('V', substr($data, 1, 4))[1];
                $width = ($bits & 0x3FFF) + 1;
                $height = (($bits >> 14) & 0x3FFF) + 1;
            } elseif ('EXIF' === $type) {
                $orientation = OrientationReader::read($data);
                $hasExif = false;
            }

            if ($width > 0 && $height > 0 && !$hasExif) {
                return $this->information($width, $height, ['webp', $orientation]);
            }
            $stream->skip($length - \strlen($data) + $length % 2);
        }

        return $this->information($width, $height, ['webp', $orientation]);
    }

    private function information(int $width, int $height, array $format): ?ImageInformation
    {
        if ($width < 1 || $height < 1) {
            return null;
        }

        if (\in_array($format[1], [5, 6, 7, 8], true)) {
            [$width, $height] = [$height, $width];
        }

        return new ImageInformation(new Dimensions(new Size($width, $height), Dimensions::ABSOLUTE, $format[1]), $format[0]);
    }

    private function jpegMarker(InspectionStream $stream): ?int
    {
        if ("\xff" !== $stream->read(1)) {
            return null;
        }

        do {
            $byte = $stream->read(1);
        } while ("\xff" === $byte);

        return '' === $byte ? null : \ord($byte);
    }
}
