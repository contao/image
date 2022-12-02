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

use Contao\Image\Exception\RuntimeException;

final class MetadataParser
{
    /**
     * @var list<AbstractContainer>
     */
    private $containers;

    /**
     * @var array<string,AbstractFormat>
     */
    private $formats;

    /**
     * @param list<AbstractFormat>    $formats
     * @param list<AbstractContainer> $containers
     */
    public function __construct(array $formats = [], array $containers = [])
    {
        array_unshift(
            $formats,
            new XmpFormat(),
            new IptcFormat(),
            new ExifFormat()
        );

        foreach ($formats as $format) {
            $this->formats[$format->getName()] = $format;
        }

        $this->containers = $containers;
        $this->containers[] = new JpegContainer($this);
        $this->containers[] = new PngContainer($this);
        $this->containers[] = new WebpContainer($this);
    }

    /**
     * @param resource|string $pathOrStream
     */
    public function parse($pathOrStream): ImageMetadata
    {
        if (!\is_string($pathOrStream) && (!\is_resource($pathOrStream) || 'stream' !== get_resource_type($pathOrStream))) {
            throw new \TypeError(sprintf('Argument 1 passed to %s() must be of the type resource|string, %s given', __METHOD__, get_debug_type($pathOrStream)));
        }

        if (\is_string($pathOrStream)) {
            $stream = @fopen($pathOrStream, 'r');

            if (!$stream) {
                return new ImageMetadata([]);
            }
        } else {
            $stream = $pathOrStream;
        }

        foreach ($this->containers as $container) {
            $magicBytes = $container->getMagicBytes();
            $bytes = fread($stream, \strlen($magicBytes));

            // TODO: rewind not supported by all streams
            fseek($stream, -\strlen($magicBytes), SEEK_CUR);

            if ($bytes === $magicBytes) {
                return new ImageMetadata($container->parse($stream));
            }
        }

        return new ImageMetadata([]);
    }

    public function applyCopyrightToFile(ImageMetadata $metadata, string $inputPath, string $outputPath): void
    {
        $input = fopen($inputPath, 'r');

        if (!$input) {
            throw new RuntimeException(sprintf('Unable to open image path "%s"', $inputPath));
        }

        $output = fopen($outputPath, 'w');

        if (!$output) {
            throw new RuntimeException(sprintf('Unable to write image to path "%s"', $outputPath));
        }

        $this->applyCopyrightToStream($metadata, $input, $output);
    }

    /**
     * @param resource $inputStream
     * @param resource $outputStream
     */
    public function applyCopyrightToStream(ImageMetadata $metadata, $inputStream, $outputStream): void
    {
        if (!\is_resource($inputStream) || 'stream' !== get_resource_type($inputStream)) {
            throw new \TypeError(sprintf('Argument 2 passed to %s() must be of the type resource, %s given', __METHOD__, get_debug_type($inputStream)));
        }

        if (!\is_resource($outputStream) || 'stream' !== get_resource_type($outputStream)) {
            throw new \TypeError(sprintf('Argument 3 passed to %s() must be of the type resource, %s given', __METHOD__, get_debug_type($outputStream)));
        }

        // Empty metadata
        if (!$metadata->getAll()) {
            stream_copy_to_stream($inputStream, $outputStream);

            return;
        }

        foreach ($this->containers as $container) {
            $magicBytes = $container->getMagicBytes();
            $bytes = fread($inputStream, \strlen($magicBytes));

            // TODO: rewind not supported by all streams
            fseek($inputStream, -\strlen($magicBytes), SEEK_CUR);

            if ($bytes === $magicBytes) {
                $container->apply($inputStream, $outputStream, $metadata);

                return;
            }
        }
    }

    public function parseFormat(string $format, string $binaryChunk): array
    {
        return $this->formats[$format]->parse($binaryChunk);
    }

    public function serializeFormat(string $format, ImageMetadata $metadata): string
    {
        return $this->formats[$format]->serialize($metadata);
    }
}
