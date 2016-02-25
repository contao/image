<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
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
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * Constructor.
     *
     * @param ResizeCalculator         $calculator The resize calculator object
     * @param Filesystem               $filesystem The filesystem object
     * @param string                   $path       The absolute image assets path
     * @param ContaoFrameworkInterface $framework  The Contao framework
     */
    public function __construct(
        ResizeCalculator $calculator,
        Filesystem $filesystem,
        $path,
        ContaoFrameworkInterface $framework
    ) {
        $this->calculator = $calculator;
        $this->filesystem = $filesystem;
        $this->path = (string) $path;
        $this->framework = $framework;
    }

    /**
     * Resizes an Image object.
     *
     * @param Image               $image          The source image
     * @param ResizeConfiguration $resizeConfig   The resize configuration
     * @param array               $imagineOptions The options for Imagine save
     * @param string|null         $targetPath     The absolute target path
     * @param boolean             $bypassCache    True to bypass the image cache
     *
     * @return Image The resized image as new object
     */
    public function resize(Image $image, ResizeConfiguration $resizeConfig, array $imagineOptions = [], $targetPath = null, $bypassCache = false)
    {
        if ($image->getDimensions()->isUndefined() || $resizeConfig->isEmpty()) {
            $image = $this->createImage($image, $image->getPath());
        }
        else {
            $image = $this->processResize($image, $resizeConfig, $imagineOptions, $bypassCache);
        }

        if (null !== $targetPath) {

            if (!$this->filesystem->isAbsolutePath($targetPath)) {
                throw new \InvalidArgumentException('"' . $targetPath . '" is not an absolute target path');
            }

            $this->filesystem->copy($image->getPath(), $targetPath);
            $image = $this->createImage($image, $targetPath);

        }

        return $image;
    }

    /**
     * Processes the resize and executes it if not already cached.
     *
     * @param Image               $image
     * @param ResizeConfiguration $resizeConfig
     * @param array               $imagineOptions
     * @param boolean             $bypassCache
     *
     * @return Image
     */
    protected function processResize(Image $image, ResizeConfiguration $resizeConfig, array $imagineOptions, $bypassCache)
    {
        $coordinates = $this->calculator->calculate(
            $resizeConfig,
            $image->getDimensions(),
            $image->getImportantPart()
        );

        $cachePath = $this->path . '/' . $this->createCachePath($image->getPath(), $coordinates);

        if ($this->filesystem->exists($cachePath) && !$bypassCache) {
            return $this->createImage($image, $cachePath);
        }

        return $this->executeResize($image, $coordinates, $cachePath, $imagineOptions);
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
