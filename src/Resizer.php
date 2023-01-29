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

use Contao\Image\Metadata\ImageMetadata;
use Contao\Image\Metadata\MetadataParser;
use Imagine\Exception\RuntimeException as ImagineRuntimeException;
use Imagine\Filter\Basic\Autorotate;
use Imagine\Image\Palette\RGB;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class Resizer implements ResizerInterface
{
    /**
     * @var Filesystem
     *
     * @internal
     */
    protected $filesystem;

    /**
     * @var string
     *
     * @internal
     */
    protected $cacheDir;

    /**
     * @var ResizeCalculator
     */
    private $calculator;

    /**
     * @var MetadataParser
     */
    private $metadataParser;

    public function __construct(string $cacheDir, ResizeCalculator $calculator = null, Filesystem $filesystem = null, MetadataParser $metadataParser = null)
    {
        if (null === $calculator) {
            $calculator = new ResizeCalculator();
        }

        if (null === $filesystem) {
            $filesystem = new Filesystem();
        }

        if (null === $metadataParser) {
            $metadataParser = new MetadataParser();
        }

        $this->cacheDir = $cacheDir;
        $this->calculator = $calculator;
        $this->filesystem = $filesystem;
        $this->metadataParser = $metadataParser;
    }

    /**
     * {@inheritdoc}
     */
    public function resize(ImageInterface $image, ResizeConfiguration $config, ResizeOptions $options): ImageInterface
    {
        if (
            $image->getDimensions()->isUndefined()
            || ($config->isEmpty() && $this->canSkipResize($image, $options))
        ) {
            $image = $this->createImage($image, $image->getPath());
        } else {
            $image = $this->processResize($image, $config, $options);
        }

        if (null !== $options->getTargetPath()) {
            $this->filesystem->copy($image->getPath(), $options->getTargetPath(), true);
            $image = $this->createImage($image, $options->getTargetPath());
        }

        return $image;
    }

    /**
     * Executes the resize operation via Imagine.
     *
     * @internal Do not call this method in your code; it will be made private in a future version
     */
    protected function executeResize(ImageInterface $image, ResizeCoordinates $coordinates, string $path, ResizeOptions $options): ImageInterface
    {
        $dir = \dirname($path);

        if (!$this->filesystem->exists($dir)) {
            $this->filesystem->mkdir($dir);
        }

        $imagineOptions = $options->getImagineOptions();
        $imagineImage = $image->getImagine()->open($image->getPath());

        if (ImageDimensions::ORIENTATION_NORMAL !== $image->getDimensions()->getOrientation()) {
            (new Autorotate())->apply($imagineImage);
        }

        $imagineImage
            ->resize($coordinates->getSize())
            ->crop($coordinates->getCropStart(), $coordinates->getCropSize())
            ->usePalette(new RGB())
            ->strip()
        ;

        if (isset($imagineOptions['interlace'])) {
            try {
                $imagineImage->interlace($imagineOptions['interlace']);
            } catch (ImagineRuntimeException $e) {
                // Ignore failed interlacing
            }
        }

        if (!isset($imagineOptions['format'])) {
            $imagineOptions['format'] = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        }

        // Fix bug with undefined index notice in Imagine
        if ('webp' === $imagineOptions['format'] && !isset($imagineOptions['webp_quality'])) {
            $imagineOptions['webp_quality'] = 80;
        }

        $tmpPath1 = $this->filesystem->tempnam($dir, 'img');
        $tmpPath2 = $this->filesystem->tempnam($dir, 'img');
        $this->filesystem->chmod([$tmpPath1, $tmpPath2], 0666, umask());

        if ($options->getPreserveCopyrightMetadata() && ($metadata = $this->getMetadata($image))->getAll()) {
            $imagineImage->save($tmpPath1, $imagineOptions);

            try {
                $this->metadataParser->applyCopyrightToFile($tmpPath1, $tmpPath2, $metadata, $options->getPreserveCopyrightMetadata());
            } catch (\Throwable $exception) {
                $this->filesystem->rename($tmpPath1, $tmpPath2, true);
            }
        } else {
            $imagineImage->save($tmpPath2, $imagineOptions);
        }

        $this->filesystem->remove($tmpPath1);

        // Atomic write operation
        $this->filesystem->rename($tmpPath2, $path, true);

        return $this->createImage($image, $path);
    }

    /**
     * Creates a new image instance for the specified path.
     *
     * @internal Do not call this method in your code; it will be made private in a future version
     */
    protected function createImage(ImageInterface $image, string $path): ImageInterface
    {
        return new Image($path, $image->getImagine(), $this->filesystem);
    }

    /**
     * Processes the resize and executes it if not already cached.
     *
     * @internal
     */
    protected function processResize(ImageInterface $image, ResizeConfiguration $config, ResizeOptions $options): ImageInterface
    {
        $coordinates = $this->calculator->calculate($config, $image->getDimensions(), $image->getImportantPart());

        // Skip resizing if it would have no effect
        if (
            $this->canSkipResize($image, $options)
            && !$image->getDimensions()->isRelative()
            && $coordinates->isEqualTo($image->getDimensions()->getSize())
        ) {
            return $this->createImage($image, $image->getPath());
        }

        $cachePath = Path::join($this->cacheDir, $this->createCachePath($image->getPath(), $coordinates, $options));

        if ($this->filesystem->exists($cachePath) && !$options->getBypassCache()) {
            return $this->createImage($image, $cachePath);
        }

        return $this->executeResize($image, $coordinates, $cachePath, $options);
    }

    private function canSkipResize(ImageInterface $image, ResizeOptions $options): bool
    {
        if (!$options->getSkipIfDimensionsMatch()) {
            return false;
        }

        if (ImageDimensions::ORIENTATION_NORMAL !== $image->getDimensions()->getOrientation()) {
            return false;
        }

        if (
            isset($options->getImagineOptions()['format'])
            && $options->getImagineOptions()['format'] !== strtolower(pathinfo($image->getPath(), PATHINFO_EXTENSION))
        ) {
            return false;
        }

        return true;
    }

    /**
     * Returns the relative target cache path.
     */
    private function createCachePath(string $path, ResizeCoordinates $coordinates, ResizeOptions $options): string
    {
        $imagineOptions = $options->getImagineOptions();
        ksort($imagineOptions);

        $hashData = array_merge(
            [
                Path::makeRelative($path, $this->cacheDir),
                filemtime($path),
                $coordinates->getHash(),
            ],
            array_keys($imagineOptions),
            array_map(
                static function ($value) {
                    return \is_array($value) ? implode(',', $value) : $value;
                },
                array_values($imagineOptions)
            )
        );

        $preserveMeta = $options->getPreserveCopyrightMetadata();

        if ($preserveMeta !== (new ResizeOptions())->getPreserveCopyrightMetadata()) {
            ksort($preserveMeta, SORT_STRING);
            $hashData[] = json_encode($preserveMeta);
        }

        $hash = substr(md5(implode('|', $hashData)), 0, 9);
        $pathinfo = pathinfo($path);
        $extension = $options->getImagineOptions()['format'] ?? strtolower($pathinfo['extension']);

        return Path::join($hash[0], $pathinfo['filename'].'-'.substr($hash, 1).'.'.$extension);
    }

    private function getMetadata(ImageInterface $image): ImageMetadata
    {
        try {
            return $this->metadataParser->parse($image->getPath());
        } catch (\Throwable $exception) {
            return new ImageMetadata([]);
        }
    }
}
