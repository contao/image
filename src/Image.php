<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Image;

use Imagine\Image\ImagineInterface;
use Imagine\Image\Point;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Image data
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
            throw new \RuntimeException($path . ' doesn\'t exist');
        }

        $this->imagine = $imagine;
        $this->filesystem = $filesystem;
        $this->path = (string) $path;
    }

    /**
     * Gets the path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Gets the dimensions
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
     * Gets the important part
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
     * Sets the important part
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
