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

use Contao\Image\Exception\InvalidImageMetadataException;

abstract class AbstractFormat
{
    public const NAME = null;
    public const DEFAULT_PRESERVE_KEYS = [];

    public function getName(): string
    {
        return static::NAME;
    }

    abstract public function serialize(ImageMetadata $metadata, array $preserveKeys): string;

    /**
     * @throws InvalidImageMetadataException
     */
    abstract public function parse(string $binaryChunk): array;

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
        return filter_var(
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
}
