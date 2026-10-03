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

final class Dimensions
{
    public const ABSOLUTE = 'absolute';

    public const RELATIVE = 'relative';

    public const UNDEFINED = 'undefined';

    public function __construct(
        private Size $size,
        private string $kind = self::ABSOLUTE,
        private int $orientation = 1,
    ) {
        if (!\in_array($kind, [self::ABSOLUTE, self::RELATIVE, self::UNDEFINED], true) || $orientation < 1 || $orientation > 8) {
            throw new InvalidArgumentException('Invalid dimension kind or orientation');
        }
    }

    public function getSize(): Size
    {
        return $this->size;
    }

    public function getOrientation(): int
    {
        return $this->orientation;
    }

    public function isRelative(): bool
    {
        return self::RELATIVE === $this->kind;
    }

    public function isUndefined(): bool
    {
        return self::UNDEFINED === $this->kind;
    }

    public function toArray(): array
    {
        return ['size' => $this->size->toArray(), 'kind' => $this->kind, 'orientation' => $this->orientation];
    }

    public static function fromArray(array $data): self
    {
        return new self(Size::fromArray($data['size']), $data['kind'], $data['orientation']);
    }
}
