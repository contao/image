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

use Contao\Image\Exception\ExceptionInterface;
use Contao\Image\Exception\ImageDecodingException;
use Contao\Image\Exception\UnsupportedImageFormatException;
use Contao\Image\Internal\InspectionStream;
use Contao\Image\Internal\RasterInspector;
use Contao\Image\Internal\SvgInspector;

final class StreamImageInspector implements ImageInspectorInterface
{
    public function __construct(private ?ImagineImageInspector $fallback = null)
    {
    }

    public function inspect(ImageSourceInterface $source): ImageInformation
    {
        $stream = new InspectionStream($source);

        try {
            $prefix = $stream->read(12);
            $stream->rewind();
            $raster = new RasterInspector();
            $information = match (true) {
                str_starts_with($prefix, "\xff\xd8") => $raster->jpeg($stream),
                str_starts_with($prefix, "\x89PNG\r\n\x1a\n") => $raster->png($stream),
                str_starts_with($prefix, 'GIF87a'), str_starts_with($prefix, 'GIF89a') => $raster->gif($stream),
                str_starts_with($prefix, 'RIFF') && 'WEBP' === substr($prefix, 8, 4) => $raster->webp($stream),
                str_starts_with($prefix, "\x1f\x8b") => (new SvgInspector())->inspect($stream, true),
                '' === trim($prefix) || str_starts_with(ltrim($prefix, "\xef\xbb\xbf \n\r\t"), '<') => (new SvgInspector())->inspect($stream, false),
                default => null,
            };
            $information ??= $this->inspectBuffered($stream->getCompletePath());
            $stream->validate();

            return $information;
        } catch (ExceptionInterface $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new ImageDecodingException('Cannot inspect '.$source->getFilename(), 0, $exception);
        } finally {
            $stream->close();
        }
    }

    private function inspectBuffered(string $path): ImageInformation
    {
        $size = @getimagesize($path);

        if (false === $size || !$size[0] || !$size[1]) {
            if (null !== $this->fallback) {
                return $this->fallback->inspectPath($path);
            }

            throw new UnsupportedImageFormatException('Image requires a compatible inspection driver');
        }
        $orientation = \function_exists('exif_read_data') ? (int) (@exif_read_data($path, 'IFD0')['Orientation'] ?? 1) : 1;

        if ($orientation < 1 || $orientation > 8) {
            $orientation = 1;
        }
        [$width, $height] = $size;

        if (\in_array($orientation, [5, 6, 7, 8], true)) {
            [$width, $height] = [$height, $width];
        }
        $format = image_type_to_extension($size[2], false);

        return new ImageInformation(new Dimensions(new Size($width, $height), Dimensions::ABSOLUTE, $orientation), 'jpeg' === $format ? 'jpg' : $format);
    }
}
