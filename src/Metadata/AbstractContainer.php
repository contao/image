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

abstract class AbstractContainer implements ImageContainerInterface
{
    /**
     * @var MetadataReaderWriter
     */
    protected $metadataReaderWriter;

    public function __construct(MetadataReaderWriter $parser)
    {
        $this->metadataReaderWriter = $parser;
    }

    public function getMagicBytesOffset(): int
    {
        return 0;
    }

    protected function parseFormat(string $format, string $binaryChunk): array
    {
        try {
            return $this->metadataReaderWriter->parseFormat($format, $binaryChunk);
        } catch (InvalidImageMetadataException $exception) {
            return [];
        }
    }
}
