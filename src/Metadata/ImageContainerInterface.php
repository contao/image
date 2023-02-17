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

use Contao\Image\Exception\InvalidImageContainerException;

interface ImageContainerInterface
{
    public function getMagicBytes(): string;

    public function getMagicBytesOffset(): int;

    /**
     * @param resource $inputStream
     * @param resource $outputStream
     *
     * @throws InvalidImageContainerException
     */
    public function apply($inputStream, $outputStream, ImageMetadata $metadata, array $preserveKeysByFormat): void;

    /**
     * @param resource $stream
     *
     * @throws InvalidImageContainerException
     */
    public function parse($stream): array;
}
