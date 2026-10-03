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

use Contao\Image\Exception\InvalidArgumentException;

final class Json
{
    public static function normalize(array $value): array
    {
        if (!array_is_list($value)) {
            ksort($value, SORT_STRING);
        }

        foreach ($value as &$item) {
            if (\is_array($item)) {
                $item = self::normalize($item);
            } elseif (!\is_scalar($item) && null !== $item) {
                throw new InvalidArgumentException('Options must contain only JSON values');
            }
        }
        json_encode($value, JSON_THROW_ON_ERROR);

        return $value;
    }

    public static function encode(array $value): string
    {
        return json_encode(self::normalize($value), JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_SLASHES);
    }
}
