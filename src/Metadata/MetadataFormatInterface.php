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

interface MetadataFormatInterface
{
    public const NAME = null;
    public const DEFAULT_PRESERVE_KEYS = [];

    public function serialize(ImageMetadata $metadata, array $preserveKeys): string;

    /**
     * @throws InvalidImageMetadataException
     */
    public function parse(string $binaryChunk): array;

    /**
     * @return array<string,list<string>> Labels as keys, values as string lists
     */
    public function toReadable(array $data): array;
}
