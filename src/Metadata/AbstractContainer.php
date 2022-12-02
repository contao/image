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

abstract class AbstractContainer
{
    abstract public function getMagicBytes(): string;

    /**
     * @param resource $inputStream
     * @param resource $outputStream
     */
    abstract public function apply($inputStream, $outputStream, ImageMetadata $metadata): void;

    /**
     * @param resource $stream
     */
    abstract public function parse($stream): array;
}
