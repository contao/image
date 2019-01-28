<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image;

use Imagine\Exception\RuntimeException as ImagineRuntimeException;
use Imagine\Image\Palette\RGB;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

class Resizer implements ResizerInterface
{
    /**
     * @var ResizeCalculatorInterface
     */
    private $calculator;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @param string                         $cacheDir
     * @param ResizeCalculatorInterface|null $calculator
     * @param Filesystem|null                $filesystem
     */
    public function __construct($cacheDir, ResizeCalculatorInterface $calculator = null, Filesystem $filesystem = null)
    {
        if (null === $calculator) {
            $calculator = new ResizeCalculator();
        }

        if (null === $filesystem) {
            $filesystem = new Filesystem();
        }

        $this->cacheDir = (string) $cacheDir;
        $this->calculator = $calculator;
        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritdoc}
     */
    public function resize(ImageInterface $image, ResizeConfigurationInterface $config, ResizeOptionsInterface $options)
    {
        if ($config->isEmpty() || $image->getDimensions()->isUndefined()) {
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
     * @param ImageInterface             $image
     * @param ResizeCoordinatesInterface $coordinates
     * @param string                     $path
     * @param ResizeOptionsInterface     $options
     *
     * @return ImageInterface
     *
     * @internal Do not call this method in your code; it will be made private in a future version
     */
    protected function executeResize(ImageInterface $image, ResizeCoordinatesInterface $coordinates, $path, ResizeOptionsInterface $options)
    {
        $dir = \dirname($path);

        if (!$this->filesystem->exists($dir)) {
            $this->filesystem->mkdir($dir);
        }

        $imagineOptions = $options->getImagineOptions();

        $imagineImage = $image
            ->getImagine()
            ->open($image->getPath())
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
     * @param ImageInterface $image
     * @param string         $path
     *
     * @return ImageInterface
     *
     * @internal Do not call this method in your code; it will be made private in a future version
     */
    protected function createImage(ImageInterface $image, $path)
    {
        return new Image($path, $image->getImagine(), $this->filesystem);
    }

    /**
     * Processes the resize and executes it if not already cached.
     *
     * @param ImageInterface               $image
     * @param ResizeConfigurationInterface $config
     * @param ResizeOptionsInterface       $options
     *
     * @return ImageInterface
     */
    private function processResize(ImageInterface $image, ResizeConfigurationInterface $config, ResizeOptionsInterface $options)
    {
        $coordinates = $this->calculator->calculate($config, $image->getDimensions(), $image->getImportantPart());

        // Skip resizing if it would have no effect
        if (!$image->getDimensions()->isRelative() && $coordinates->isEqualTo($image->getDimensions()->getSize())) {
            return $this->createImage($image, $image->getPath());
        }

        $cachePath = $this->cacheDir.'/'.$this->createCachePath($image->getPath(), $coordinates, $options);

        if ($this->filesystem->exists($cachePath) && !$options->getBypassCache()) {
            return $this->createImage($image, $cachePath);
        }

        return $this->executeResize($image, $coordinates, $cachePath, $options);
    }

    /**
     * Creates the target cache path.
     *
     * @param string                     $path
     * @param ResizeCoordinatesInterface $coordinates
     * @param ResizeOptionsInterface     $options
     *
     * @return string The relative target path
     */
    private function createCachePath($path, ResizeCoordinatesInterface $coordinates, ResizeOptionsInterface $options)
    {
        $pathinfo = pathinfo($path);
        $imagineOptions = $options->getImagineOptions();
        ksort($imagineOptions);

        $hash = substr(md5(implode('|', array_merge(
            [
                Path::makeRelative($path, $this->cacheDir),
                filemtime($path),
                $coordinates->getHash(),
            ],
            array_keys($imagineOptions),
            array_values($imagineOptions)
        ))), 0, 9);

        return $hash[0].'/'.$pathinfo['filename'].'-'.substr($hash, 1).'.'.$pathinfo['extension'];
    }
}
