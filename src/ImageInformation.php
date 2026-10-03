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

use Contao\Image\Exception\InvalidArgumentException;

final class ImageInformation
{
    public function __construct(
        private Dimensions $dimensions,
        private string $format,
    ) {
        if (!preg_match('/^[a-z0-9]+$/D', $format)) {
            throw new InvalidArgumentException('Invalid image format');
        }
    }

    public function getDimensions(): Dimensions
    {
        return $this->dimensions;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function toArray(): array
    {
        return ['dimensions' => $this->dimensions->toArray(), 'format' => $this->format];
    }

    public static function fromArray(array $data): self
    {
        return new self(Dimensions::fromArray($data['dimensions']), $data['format']);
    }
}
