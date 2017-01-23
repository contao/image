<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image;

use Imagine\Exception\RuntimeException as ImagineRuntimeException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Resizer class.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
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
     * Constructor.
     *
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
        if ($image->getDimensions()->isUndefined() || $config->isEmpty()) {
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
        if ($coordinates->isEqualTo($image->getDimensions()->getSize()) && !$image->getDimensions()->isRelative()) {
            return $this->createImage($image, $image->getPath());
        }

        $cachePath = $this->cacheDir.'/'.$this->createCachePath($image->getPath(), $coordinates, $options);

        if ($this->filesystem->exists($cachePath) && !$options->getBypassCache()) {
            return $this->createImage($image, $cachePath);
        }

        return $this->executeResize($image, $coordinates, $cachePath, $options);
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
        if (!$this->filesystem->exists(dirname($path))) {
            $this->filesystem->mkdir(dirname($path));
        }

        $imagineOptions = $options->getImagineOptions();

        $imagineImage = $image
            ->getImagine()
            ->open($image->getPath())
            ->resize($coordinates->getSize())
            ->crop($coordinates->getCropStart(), $coordinates->getCropSize())
        ;

        if (isset($imagineOptions['interlace'])) {
            try {
                $imagineImage->interlace($imagineOptions['interlace']);
            } catch (ImagineRuntimeException $e) {
                // Ignore failed interlacing
            }
        }

        $imagineImage->save($path, $imagineOptions);

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
     * Creates the target cache path.
     *
     * @param string                     $path
     * @param ResizeCoordinatesInterface $coordinates
     * @param ResizeOptionsInterface     $options
     *
     * @return string The realtive target path
     */
    private function createCachePath($path, ResizeCoordinatesInterface $coordinates, ResizeOptionsInterface $options)
    {
        $pathinfo = pathinfo($path);
        $imagineOptions = $options->getImagineOptions();
        ksort($imagineOptions);

        $hash = substr(md5(implode('|', array_merge(
            [$path, filemtime($path), $coordinates->getHash()],
            array_keys($imagineOptions),
            array_values($imagineOptions)
        ))), 0, 9);

        return substr($hash, 0, 1).'/'.$pathinfo['filename'].'-'.substr($hash, 1).'.'.$pathinfo['extension'];
    }
}
