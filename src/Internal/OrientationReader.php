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

final class OrientationReader
{
    public static function read(string $tiff): int
    {
        if (str_starts_with($tiff, "Exif\0\0")) {
            $tiff = substr($tiff, 6);
        }

        if (\strlen($tiff) < 8 || !\in_array(substr($tiff, 0, 2), ['II', 'MM'], true)) {
            return 1;
        }
        $short = str_starts_with($tiff, 'II') ? 'v' : 'n';
        $long = 'v' === $short ? 'V' : 'N';
        $offset = unpack($long, substr($tiff, 4, 4))[1];

        if ($offset + 2 > \strlen($tiff)) {
            return 1;
        }
        $count = unpack($short, substr($tiff, $offset, 2))[1];

        for ($i = 0; $i < $count; ++$i) {
            $entry = substr($tiff, $offset + 2 + $i * 12, 12);

            if (12 !== \strlen($entry)) {
                return 1;
            }

            if (274 === unpack($short, substr($entry, 0, 2))[1] && 3 === unpack($short, substr($entry, 2, 2))[1]) {
                $orientation = unpack($short, substr($entry, 8, 2))[1];

                return $orientation >= 1 && $orientation <= 8 ? $orientation : 1;
            }
        }

        return 1;
    }
}
