<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image;

use Imagine\Image\Box;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Point;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Image class.
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
     * Constructor.
     *
     * @param string           $path
     * @param ImagineInterface $imagine
     * @param Filesystem|null  $filesystem
     */
    public function __construct($path, ImagineInterface $imagine, Filesystem $filesystem = null)
    {
        if (null === $filesystem) {
            $filesystem = new Filesystem();
        }

        if (!$filesystem->exists($path)) {
            throw new \InvalidArgumentException($path.' does not exist');
        }

        if (is_dir($path)) {
            throw new \InvalidArgumentException($path.' is a directory');
        }

        $this->path = (string) $path;
        $this->imagine = $imagine;
        $this->filesystem = $filesystem;
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
            substr($this->path, 0, strlen($rootDir) + 1) === $rootDir.'/'
            || substr($this->path, 0, strlen($rootDir) + 1) === $rootDir.'\\'
        ) {
            $url = substr($this->path, strlen($rootDir) + 1);
        } else {
            throw new \InvalidArgumentException('Path "'.$this->path.'" is not inside root directory "'.$rootDir.'"');
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
            $size = @getimagesize($this->getPath()); // try native getimagesize() for better performance

            if (!empty($size[0]) && !empty($size[1])) {
                $this->dimensions = new ImageDimensions(new Box($size[0], $size[1]));
            } else {
                $this->dimensions = new ImageDimensions($this->imagine->open($this->getPath())->getSize());
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
            $this->importantPart = new ImportantPart(new Point(0, 0), $this->getDimensions()->getSize());
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
