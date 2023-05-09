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

use Contao\Image\Exception\InvalidArgumentException;
use Contao\Image\Exception\InvalidImageContainerException;
use Contao\Image\Exception\InvalidImageMetadataException;

/**
 * @final
 */
class MetadataReaderWriter
{
    /**
     * @var list<ImageContainerInterface>
     */
    private $containers;

    /**
     * @var array<string,MetadataFormatInterface>
     */
    private $formats;

    /**
     * @param iterable<MetadataFormatInterface> $formats
     * @param iterable<ImageContainerInterface> $containers
     */
    public function __construct(iterable $formats = [], iterable $containers = [])
    {
        foreach ([new XmpFormat(), new IptcFormat(), new ExifFormat(), new PngFormat(), new GifFormat()] as $format) {
            $this->formats[$format::NAME] = $format;
        }

        foreach ($formats as $format) {
            $this->formats[$format::NAME] = $format;
        }

        $this->containers = \is_array($containers)
            ? array_values($containers)
            : iterator_to_array($containers, false)
        ;

        $this->containers[] = new JpegContainer($this);
        $this->containers[] = new PngContainer($this);
        $this->containers[] = new WebpContainer($this);
        $this->containers[] = new GifContainer($this);
        $this->containers[] = new AvifContainer($this);
        $this->containers[] = new HeicContainer($this);
        $this->containers[] = new JxlContainer($this);
    }

    /**
     * @param resource|string $pathOrStream
     *
     * @throws InvalidImageContainerException
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

        if ($container = $this->findContainer($stream)) {
            return new ImageMetadata($container->parse($stream));
        }

        return new ImageMetadata([]);
    }

    public function applyCopyrightToFile(string $inputPath, string $outputPath, ImageMetadata $metadata, array $preserveKeysByFormat): void
    {
        $input = fopen($inputPath, 'r');

        if (!$input) {
            throw new InvalidArgumentException(sprintf('Unable to open image path "%s"', $inputPath));
        }

        $output = fopen($outputPath, 'w');

        if (!$output) {
            throw new InvalidArgumentException(sprintf('Unable to write image to path "%s"', $outputPath));
        }

        $this->applyCopyrightToStream($input, $output, $metadata, $preserveKeysByFormat);
    }

    /**
     * @param resource $inputStream
     * @param resource $outputStream
     */
    public function applyCopyrightToStream($inputStream, $outputStream, ImageMetadata $metadata, array $preserveKeysByFormat): void
    {
        if (!\is_resource($inputStream) || 'stream' !== get_resource_type($inputStream)) {
            throw new \TypeError(sprintf('Argument 2 passed to %s() must be of the type resource, %s given', __METHOD__, get_debug_type($inputStream)));
        }

        if (!\is_resource($outputStream) || 'stream' !== get_resource_type($outputStream)) {
            throw new \TypeError(sprintf('Argument 3 passed to %s() must be of the type resource, %s given', __METHOD__, get_debug_type($outputStream)));
        }

        if ($metadata->getAll() && $container = $this->findContainer($inputStream)) {
            $container->apply($inputStream, $outputStream, $metadata, $preserveKeysByFormat);

            return;
        }

        stream_copy_to_stream($inputStream, $outputStream);
    }

    /**
     * @throws InvalidImageMetadataException
     */
    public function parseFormat(string $format, string $binaryChunk): array
    {
        return $this->formats[$format]->parse($binaryChunk);
    }

    public function serializeFormat(string $format, ImageMetadata $metadata, array $preserveKeys): string
    {
        return $this->formats[$format]->serialize($metadata, $preserveKeys);
    }

    /**
     * @return array<string,array<string,list<string>>> One array per format with labels as keys, values as string lists
     */
    public function toReadable(ImageMetadata $metadata): array
    {
        $readable = [];

        foreach ($metadata->getAll() as $format => $data) {
            $readable[$format] = $this->formats[$format]->toReadable($data);
        }

        return $readable;
    }

    /**
     * @param resource $stream
     */
    private function findContainer($stream): ?ImageContainerInterface
    {
        foreach ($this->containers as $container) {
            $magicBytes = $container->getMagicBytes();
            $offset = $container->getMagicBytesOffset();
            $length = $offset + \strlen($magicBytes);
            $bytes = substr(fread($stream, $length), $offset);

            if (0 !== fseek($stream, -$length, SEEK_CUR)) {
                $streamMeta = stream_get_meta_data($stream);

                throw new InvalidArgumentException(sprintf('Unable to rewind "%s" stream "%s"', $streamMeta['stream_type'], $streamMeta['uri']));
            }

            if ($bytes === $magicBytes) {
                return $container;
            }
        }

        return null;
    }
}
