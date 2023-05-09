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

abstract class AbstractFormat implements MetadataFormatInterface
{
    public function toReadable(array $data): array
    {
        $data = $this->prefixIntKeys($data, static::NAME.'_');

        return array_map(
            function ($value) {
                return $this->ensureStringList($value);
            },
            $data
        );
    }

    /**
     * @param array|string $values
     */
    protected function filterValue($values): array
    {
        $return = [];

        foreach ((array) $values as $value) {
            $value = trim((string) $value);

            if (!\strlen($value)) {
                continue;
            }

            $return[] = $value;
        }

        return $return;
    }

    protected function toUtf8(array $values): array
    {
        return (array) filter_var(
            $values,
            FILTER_CALLBACK,
            [
                'options' => static function (string $value): string {
                    $value = str_replace("\x00", "\u{FFFD}", $value);

                    // Already valid UTF-8
                    if (1 === preg_match('//u', $value)) {
                        return $value;
                    }

                    $substituteCharacter = mb_substitute_character();
                    mb_substitute_character(0xFFFD);

                    $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');

                    mb_substitute_character($substituteCharacter);

                    return $value;
                },
            ]
        );
    }

    protected function prefixIntKeys(array $data, string $prefix): array
    {
        foreach ($data as $key => $value) {
            if (\is_int($key)) {
                $data[$prefix.$key] = $value;
                unset($data[$key]);
            }
        }

        return $data;
    }

    /**
     * @return list<string>
     */
    protected function ensureStringList($value): array
    {
        $value = array_map(
            function ($value) {
                if (\is_array($value)) {
                    return implode(', ', $this->ensureStringList($value));
                }

                return trim((string) $value);
            },
            (array) $value
        );

        return array_values(
            array_filter(
                $value,
                static function ($value) {
                    return '' !== $value;
                }
            )
        );
    }
}
