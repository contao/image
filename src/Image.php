<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image;

use Imagine\Image\Box;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Point;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Image data.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class Image implements ImageInterface
{
    /**
     * @var ImagineInterface
     */
    private $imagine;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $path;

    /**
     * @var ImageDimensionsInterface
     */
    private $dimensions;

    /**
     * @var ImportantPartInterface
     */
    private $importantPart;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        ImagineInterface $imagine,
        Filesystem $filesystem,
        $path
    ) {
        if (!$filesystem->exists($path)) {
            throw new \InvalidArgumentException($path . ' doesn\'t exist');
        }
        if (is_dir($path)) {
            throw new \InvalidArgumentException($path . ' is a directory');
        }

        $this->imagine = $imagine;
        $this->filesystem = $filesystem;
        $this->path = (string) $path;
    }

    /**
     * {@inheritdoc}
     */
    public function getImagine()
    {
        return $this->imagine;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl($rootDir)
    {
        if (
            substr($this->path, 0, strlen($rootDir) + 1) === $rootDir . '/'
            || substr($this->path, 0, strlen($rootDir) + 1) === $rootDir . '\\'
        ) {
            $url = substr($this->path, strlen($rootDir) + 1);
        } else {
            throw new \InvalidArgumentException('Path "' . $this->path . '" is not inside root directory "' . $rootDir . '"');
        }

        $url = str_replace('%2F', '/', rawurlencode($url));

        return $url;
    }

    /**
     * {@inheritdoc}
     */
    public function getDimensions()
    {
        if (null === $this->dimensions) {

            // Try native getimagesize() for better performance
            $size = @getimagesize($this->getPath());
            if (!empty($size[0]) && !empty($size[1])) {
                $this->dimensions = new ImageDimensions(new Box($size[0], $size[1]));
            }

            // Fallback to Imagine
            else {
                $this->dimensions = new ImageDimensions(
                    $this->imagine->open($this->getPath())->getSize()
                );
            }
        }

        return $this->dimensions;
    }

    /**
     * {@inheritdoc}
     */
    public function getImportantPart()
    {
        if (null === $this->importantPart) {
            $this->importantPart = new ImportantPart(
                new Point(0, 0),
                $this->getDimensions()->getSize()
            );
        }

        return $this->importantPart;
    }

    /**
     * {@inheritdoc}
     */
    public function setImportantPart(ImportantPartInterface $importantPart = null)
    {
        $this->importantPart = $importantPart;

        return $this;
    }
}
