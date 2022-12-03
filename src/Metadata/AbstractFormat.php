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

abstract class AbstractFormat
{
    public const NAME = null;

    public function getName(): string
    {
        return static::NAME;
    }

    abstract public function serialize(ImageMetadata $metadata): string;

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
}
