<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image;

use Contao\Image\Event\ContaoImageEvents;
use Contao\Image\Event\ResizeImageEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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
    private $path;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        $path,
        ResizeCalculatorInterface $calculator = null,
        Filesystem $filesystem = null,
        EventDispatcherInterface $eventDispatcher = null
    ) {
        if (null === $calculator) {
            $calculator = new ResizeCalculator();
        }

        if (null === $filesystem) {
            $filesystem = new Filesystem();
        }

        $this->path = (string) $path;
        $this->calculator = $calculator;
        $this->filesystem = $filesystem;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function resize(
        ImageInterface $image,
        ResizeConfigurationInterface $config,
        ResizeOptionsInterface $options
    ) {
        if ($image->getDimensions()->isUndefined() || $config->isEmpty()) {
            $image = $this->createImage($image, $image->getPath());
        } else {
            $image = $this->getResizedImage($image, $config, $options);
        }

        if (null !== $options->getTargetPath()) {
            $this->filesystem->copy($image->getPath(), $options->getTargetPath(), true);
            $image = $this->createImage($image, $options->getTargetPath());
        }

        return $image;
    }

    /**
     * Returns the resized image.
     *
     * @param ImageInterface               $image
     * @param ResizeConfigurationInterface $config
     * @param ResizeOptionsInterface       $options
     *
     * @return ImageInterface
     */
    private function getResizedImage(
        ImageInterface $image,
        ResizeConfigurationInterface $config,
        ResizeOptionsInterface $options
    ) {
        $coordinates = $this->calculator->calculate($config, $image->getDimensions(), $image->getImportantPart());

        // Skip resizing if it would have no effect
        if ($coordinates->isEqualTo($image->getDimensions()->getSize()) && !$image->getDimensions()->isRelative()) {
            return $this->createImage($image, $image->getPath());
        }

        $cachePath = $this->path.'/'.$this->createCachePath($image->getPath(), $coordinates);

        // Return the cached image
        if ($this->filesystem->exists($cachePath) && !$options->getBypassCache()) {
            return $this->createImage($image, $cachePath);
        }

        $resizedImage = $this->getResizedImageFromEvent($image, $coordinates, $cachePath, $options);

        // Return a custom resized image
        if (null !== $resizedImage) {
            return $resizedImage;
        }

        if (!$this->filesystem->exists(dirname($cachePath))) {
            $this->filesystem->mkdir(dirname($cachePath));
        }

        $image
            ->getImagine()
            ->open($image->getPath())
            ->resize($coordinates->getSize())
            ->crop($coordinates->getCropStart(), $coordinates->getCropSize())
            ->save($cachePath, $options->getImagineOptions())
        ;

        return $this->createImage($image, $cachePath);
    }

    /**
     * Returns a resized image from an event.
     *
     * @param ImageInterface             $image
     * @param ResizeCoordinatesInterface $coordinates
     * @param string                     $path
     * @param ResizeOptionsInterface     $options
     *
     * @return ImageInterface|null
     */
    private function getResizedImageFromEvent(
        ImageInterface $image,
        ResizeCoordinatesInterface $coordinates,
        $path,
        ResizeOptionsInterface $options
    ) {
        if (null === $this->eventDispatcher) {
            return null;
        }

        $event = new ResizeImageEvent($image, $coordinates, $path, $options);
        $this->eventDispatcher->dispatch(ContaoImageEvents::RESIZE_IMAGE, $event);

        return $event->getResizedImage();
    }

    /**
     * Creates a new image instance for the specified path.
     *
     * @param ImageInterface $image
     * @param string         $path
     *
     * @return ImageInterface
     */
    private function createImage(ImageInterface $image, $path)
    {
        return new Image($path, $image->getImagine(), $this->filesystem);
    }

    /**
     * Creates the target cache path.
     *
     * @param string                     $path
     * @param ResizeCoordinatesInterface $coordinates
     *
     * @return string The realtive target path
     */
    private function createCachePath($path, ResizeCoordinatesInterface $coordinates)
    {
        $pathinfo = pathinfo($path);
        $hash = substr(md5(implode('|', [$path, filemtime($path), $coordinates->getHash()])), 0, 9);

        return substr($hash, 0, 1).'/'.$pathinfo['filename'].'-'.substr($hash, 1).'.'.$pathinfo['extension'];
    }
}
