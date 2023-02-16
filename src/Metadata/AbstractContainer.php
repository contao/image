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
use Contao\Image\Exception\InvalidImageMetadataException;

abstract class AbstractContainer
{
    /**
     * @var MetadataReaderWriter
     */
    protected $parser;

    public function __construct(MetadataReaderWriter $parser)
    {
        $this->parser = $parser;
    }

    abstract public function getMagicBytes(): string;

    public function getMagicBytesOffset(): int
    {
        return 0;
    }

    /**
     * @param resource $inputStream
     * @param resource $outputStream
     *
     * @throws InvalidImageContainerException
     */
    abstract public function apply($inputStream, $outputStream, ImageMetadata $metadata, array $preserveKeysByFormat): void;

    /**
     * @param resource $stream
     *
     * @throws InvalidImageContainerException
     */
    abstract public function parse($stream): array;

    protected function parseFormat(string $format, string $binaryChunk): array
    {
        try {
            return $this->parser->parseFormat($format, $binaryChunk);
        } catch (InvalidImageMetadataException $exception) {
            return [];
        }
    }
}
