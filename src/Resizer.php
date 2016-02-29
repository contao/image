<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Resizes Image objects.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class Resizer
{
    /**
     * @var ResizeCalculator
     */
    private $calculator;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $path;

    /**
     * Constructor.
     *
     * @param ResizeCalculator         $calculator The resize calculator object
     * @param Filesystem               $filesystem The filesystem object
     * @param string                   $path       The absolute image assets path
     */
    public function __construct(
        ResizeCalculator $calculator,
        Filesystem $filesystem,
        $path
    ) {
        $this->calculator = $calculator;
        $this->filesystem = $filesystem;
        $this->path = (string) $path;
    }

    /**
     * Resizes an Image object.
     *
     * @param Image               $image   The source image
     * @param ResizeConfiguration $config  The resize configuration
     * @param ResizeOptions       $options The resize options
     *
     * @return Image The resized image as new object
     */
    public function resize(Image $image, ResizeConfiguration $config, ResizeOptions $options)
    {
        if ($image->getDimensions()->isUndefined() || $config->isEmpty()) {
            $image = $this->createImage($image, $image->getPath());
        }
        else {
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
     * @param Image               $image
     * @param ResizeConfiguration $config
     * @param ResizeOptions       $options
     *
     * @return Image
     */
    protected function processResize(Image $image, ResizeConfiguration $config, ResizeOptions $options)
    {
        $coordinates = $this->calculator->calculate(
            $config,
            $image->getDimensions(),
            $image->getImportantPart()
        );

        if ($coordinates->equals($image->getDimensions()->getSize())) {
            return $this->createImage($image, $image->getPath());
        }

        $cachePath = $this->path . '/' . $this->createCachePath($image->getPath(), $coordinates);

        if ($this->filesystem->exists($cachePath) && !$options->getBypassCache()) {
            return $this->createImage($image, $cachePath);
        }

        return $this->executeResize($image, $coordinates, $cachePath, $options->getImagineOptions());
    }

    /**
     * Executes the resize operation via Imagine.
     *
     * @param Image             $image
     * @param ResizeCoordinates $coordinates
     * @param string            $path
     * @param array             $imagineOptions
     *
     * @return Image
     */
    protected function executeResize(Image $image, ResizeCoordinates $coordinates, $path, array $imagineOptions)
    {
        if (!$this->filesystem->exists(dirname($path))) {
            $this->filesystem->mkdir(dirname($path));
        }

        $image
            ->getImagine()
            ->open($image->getPath())
            ->resize($coordinates->getSize())
            ->crop($coordinates->getCropStart(), $coordinates->getCropSize())
            ->save($path, $imagineOptions)
        ;

        return $this->createImage($image, $path);
    }

    /**
     * Creates a new image instance for the specified path.
     *
     * @param Image  $image
     * @param string $path
     *
     * @return Image
     */
    protected function createImage(Image $image, $path)
    {
        return new Image($image->getImagine(), $this->filesystem, $path);
    }

    /**
     * Creates the target cache path.
     *
     * @param string            $path        The source image path
     * @param ResizeCoordinates $coordinates The resize coordinates
     *
     * @return string The realtive target path
     */
    protected function createCachePath($path, ResizeCoordinates $coordinates)
    {
        $hash = substr(md5(implode('|', [
            $path,
            filemtime($path),
            $coordinates->getHash(),
        ])), 0, 9);

        $pathinfo = pathinfo($path);

        return substr($hash, 0, 1)
            . '/' . $pathinfo['filename']
            . '-' . substr($hash, 1)
            . '.' . $pathinfo['extension'];
    }
}
