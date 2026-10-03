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
use Contao\Image\Exception\ImageEncodingException;
use Contao\Image\Internal\Streams;
use Contao\Image\Metadata\ImageMetadata;
use Contao\Image\Metadata\MetadataReaderWriter;
use Contao\ImagineSvg\Image as SvgImage;
use Contao\ImagineSvg\Imagine as SvgImagine;
use Imagine\Exception\InvalidArgumentException;
use Imagine\Exception\RuntimeException;
use Imagine\Filter\Basic\Autorotate;
use Imagine\Gd\Image as GdImage;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Palette\RGB;
use Imagine\Image\Point as ImaginePoint;

final class ImagineImageDecoderEncoder implements ImageDecoderEncoderInterface
{
    public function __construct(private ImagineInterface $imagine, private ?ImagineInterface $svg = null, private MetadataReaderWriter $metadata = new MetadataReaderWriter())
    {
    }

    public function transform(ImageSourceInterface $source, ImageTransformation $transformation)
    {
        $snapshot = Streams::snapshot($source);

        if ($transformation->isCopy()) {
            return $snapshot;
        }
        $output = null;

        try {
            $output = Streams::temporary();
            $path = stream_get_meta_data($snapshot)['uri'];
            $driver = \in_array($transformation->getSourceInformation()->getFormat(), ['svg', 'svgz'], true) ? ($this->svg ?? new SvgImagine()) : $this->imagine;

            try {
                $image = $driver->open($path);
            } catch (\Throwable $exception) {
                throw new ImageDecodingException('Unable to decode '.$source->getFilename(), 0, $exception);
            }
            $options = $this->mapOptions($transformation);
            $this->modify($image, $transformation, $options);
            $image->save(stream_get_meta_data($output)['uri'], $options);
            rewind($output);

            return $this->preserveMetadata($snapshot, $output, $transformation->getOptions());
        } catch (\Throwable $exception) {
            if (\is_resource($output)) {
                fclose($output);
            }

            if ($exception instanceof ExceptionInterface) {
                throw $exception;
            }

            throw new ImageEncodingException('Unable to transform '.$source->getFilename(), 0, $exception);
        } finally {
            fclose($snapshot);
        }
    }

    public function mapOptions(ImageTransformation $transformation): array
    {
        $encoding = $transformation->getOptions()->getEncoding();
        $options = $encoding->getAdapterOptions('imagine');
        $common = $encoding->getCommonOptions();
        $format = $transformation->getOutputInformation()->getFormat();
        $options['format'] = $format;

        if (isset($common['quality'])) {
            $options['quality'] = $common['quality'];
            $options[('jpg' === $format ? 'jpeg' : $format).'_quality'] = $common['quality'];

            if ('png' === $format) {
                $options['png_compression_level'] = (int) round((100 - $common['quality']) * 9 / 100);
            }
        }

        if (isset($common['png_compression'])) {
            $options['png_compression_level'] = $common['png_compression'];
        }

        if (isset($common['interlacing'])) {
            $options['interlace'] = $common['interlacing'];
        }

        if (isset($common['resampling'])) {
            $options['resampling-filter'] = ['auto' => ImageInterface::FILTER_UNDEFINED, 'nearest' => ImageInterface::FILTER_POINT, 'box' => ImageInterface::FILTER_BOX, 'linear' => ImageInterface::FILTER_TRIANGLE, 'cubic' => ImageInterface::FILTER_CUBIC, 'lanczos' => ImageInterface::FILTER_LANCZOS][$common['resampling']];
        }
        // Retain the established WebP default for custom and older Imagine drivers
        if ('webp' === $format && !isset($options['webp_quality'])) {
            $options['webp_quality'] = 80;
        }

        return $options;
    }

    private function modify(ImageInterface $image, ImageTransformation $transformation, array $options): void
    {
        if (1 !== $transformation->getSourceInformation()->getDimensions()->getOrientation()) {
            (new Autorotate())->apply($image);
        }
        $filter = $image instanceof SvgImage || $image instanceof GdImage ? ImageInterface::FILTER_UNDEFINED : ($options['resampling-filter'] ?? ImageInterface::FILTER_LANCZOS);
        $coordinates = $transformation->getCoordinates();
        $image->resize(new Box($coordinates->getSize()->getWidth(), $coordinates->getSize()->getHeight()), $filter)
            ->crop(new ImaginePoint($coordinates->getCropStart()->getX(), $coordinates->getCropStart()->getY()), new Box($coordinates->getCropSize()->getWidth(), $coordinates->getCropSize()->getHeight()))
            ->usePalette(new RGB())->strip();

        if (isset($options['interlace'])) {
            try {
                $image->interlace($options['interlace']);
            } catch (InvalidArgumentException|RuntimeException) { /* Ignore failed interlacing, preserving existing behavior */
            }
        }
    }

    /**
     * @param resource $input @param resource $output @return resource
     */
    private function preserveMetadata($input, $output, ProcessingOptions $options)
    {
        if (!$options->getPreserveCopyrightMetadata()) {
            return $output;
        }

        try {
            rewind($input);
            $metadata = $this->metadata->parse($input);
        } catch (\Throwable) {
            $metadata = new ImageMetadata([]);
        }

        if (!$metadata->getAll()) {
            return $output;
        }
        $preserved = Streams::temporary();

        try {
            $this->metadata->applyCopyrightToStream($output, $preserved, $metadata, $options->getPreserveCopyrightMetadata());
            rewind($preserved);
            fclose($output);

            return $preserved;
        } catch (\Throwable) {
            fclose($preserved);
            rewind($output);

            return $output;
        }
    }
}
