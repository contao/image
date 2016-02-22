<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image;

use Imagine\Image\ImagineInterface;
use Imagine\Image\Point;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Image data.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class Image
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
     * @var ImageDimensions
     */
    private $dimensions;

    /**
     * @var ImportantPart
     */
    private $importantPart;

    /**
     * Constructor.
     *
     * @param ImagineInterface $imagine    The imagine object
     * @param Filesystem       $filesystem The filesystem object
     * @param string           $path       The path to the file
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
     * Gets the imagine instance.
     *
     * @return ImagineInterface
     */
    public function getImagine()
    {
        return $this->imagine;
    }

    /**
     * Gets the path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Gets the URL relative to the specified root directory
     *
     * @param string $rootDir
     *
     * @return string
     */
    public function getUrl($rootDir)
    {
        if (
            substr($this->path, 0, strlen($rootDir) + 1) === $rootDir . '/'
            || substr($this->path, 0, strlen($rootDir) + 1) === $rootDir . '\\'
        ) {
            $url = substr($this->path, strlen($rootDir) + 1);
        }
        else {
            throw new \InvalidArgumentException('Path "' . $this->path . '" is not inside root directory "' . $rootDir . '"');
        }

        $url = str_replace('%2F', '/', rawurlencode($url));

        return $url;
    }

    /**
     * Gets the dimensions.
     *
     * @return ImageDimensions
     */
    public function getDimensions()
    {
        if (null === $this->dimensions) {
            $this->dimensions = new ImageDimensions(
                $this->imagine->open($this->getPath())->getSize()
            );
        }

        return $this->dimensions;
    }

    /**
     * Gets the important part.
     *
     * @return ImportantPart
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
     * Sets the important part.
     *
     * @param ImportantPart $importantPart The important part
     *
     * @return self
     */
    public function setImportantPart(ImportantPart $importantPart = null)
    {
        $this->importantPart = $importantPart;

        return $this;
    }
}
