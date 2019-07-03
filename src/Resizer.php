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

use Imagine\Exception\RuntimeException as ImagineRuntimeException;
use Imagine\Filter\Basic\Autorotate;
use Imagine\Image\Palette\RGB;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

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
     * @var ResizeCalculatorInterface
     */
    private $calculator;

    public function __construct(string $cacheDir, ResizeCalculatorInterface $calculator = null, Filesystem $filesystem = null)
    {
        if (null === $calculator) {
            $calculator = new ResizeCalculator();
        }

        if (null === $filesystem) {
            $filesystem = new Filesystem();
        }

        $this->cacheDir = $cacheDir;
        $this->calculator = $calculator;
        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritdoc}
     */
    public function resize(ImageInterface $image, ResizeConfigurationInterface $config, ResizeOptionsInterface $options): ImageInterface
    {
        $extension = strtolower(pathinfo($image->getPath(), PATHINFO_EXTENSION));

        if (
            $options->getSkipIfDimensionsMatch()
            && ($options->getImagineOptions()['format'] ?? $extension) === $extension
            && ImageDimensionsInterface::ORIENTATION_NORMAL === $image->getDimensions()->getOrientation()
            && ($config->isEmpty() || $image->getDimensions()->isUndefined())
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
    protected function executeResize(ImageInterface $image, ResizeCoordinatesInterface $coordinates, string $path, ResizeOptionsInterface $options): ImageInterface
    {
        $dir = \dirname($path);

        if (!$this->filesystem->exists($dir)) {
            $this->filesystem->mkdir($dir);
        }

        $imagineOptions = $options->getImagineOptions();
        $imagineImage = $image->getImagine()->open($image->getPath());

        if (ImageDimensionsInterface::ORIENTATION_NORMAL !== $image->getDimensions()->getOrientation()) {
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

        // Atomic write operation
        $tmpPath = $this->filesystem->tempnam($dir, 'img');
        $this->filesystem->chmod($tmpPath, 0666, umask());
        $imagineImage->save($tmpPath, $imagineOptions);
        $this->filesystem->rename($tmpPath, $path, true);

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
    protected function processResize(ImageInterface $image, ResizeConfigurationInterface $config, ResizeOptionsInterface $options): ImageInterface
    {
        $coordinates = $this->calculator->calculate($config, $image->getDimensions(), $image->getImportantPart());
        $extension = strtolower(pathinfo($image->getPath(), PATHINFO_EXTENSION));

        // Skip resizing if it would have no effect
        if (
            $options->getSkipIfDimensionsMatch()
            && ($options->getImagineOptions()['format'] ?? $extension) === $extension
            && !$image->getDimensions()->isRelative()
            && ImageDimensionsInterface::ORIENTATION_NORMAL === $image->getDimensions()->getOrientation()
            && $coordinates->isEqualTo($image->getDimensions()->getSize())
        ) {
            return $this->createImage($image, $image->getPath());
        }

        $cachePath = $this->cacheDir.'/'.$this->createCachePath($image->getPath(), $coordinates, $options);

        if ($this->filesystem->exists($cachePath) && !$options->getBypassCache()) {
            return $this->createImage($image, $cachePath);
        }

        return $this->executeResize($image, $coordinates, $cachePath, $options);
    }

    /**
     * Returns the relative target cache path.
     */
    private function createCachePath(string $path, ResizeCoordinatesInterface $coordinates, ResizeOptionsInterface $options): string
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

        $hash = substr(md5(implode('|', $hashData)), 0, 9);
        $pathinfo = pathinfo($path);
        $extension = $options->getImagineOptions()['format'] ?? strtolower($pathinfo['extension']);

        return $hash[0].'/'.$pathinfo['filename'].'-'.substr($hash, 1).'.'.$extension;
    }
}
