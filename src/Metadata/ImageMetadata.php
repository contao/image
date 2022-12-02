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

final class ImageMetadata
{
    /**
     * @var array<string,array>
     */
    private $byFormat;

    /**
     * @param array<string,array> $byFormat
     */
    public function __construct(array $byFormat)
    {
        $this->byFormat = $byFormat;
    }

    public function getFormat(string $format): array
    {
        return $this->byFormat[$format] ?? [];
    }

    /**
     * @return array<string,array>
     */
    public function getAll(): array
    {
        return $this->byFormat;
    }
}
