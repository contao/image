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
use Webmozart\PathUtil\Path;

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
    public function getUrl($rootDir, $prefix = '')
    {
        if (!Path::isBasePath($rootDir, $this->path)) {
            throw new \InvalidArgumentException(sprintf('Path "%s" is not inside root directory "%s"', $this->path, $rootDir));
        }

        $url = Path::makeRelative($this->path, $rootDir);

        $url = str_replace('%2F', '/', rawurlencode($url));

        return $prefix . $url;
    }

    /**
     * {@inheritdoc}
     */
    public function getDimensions()
    {
        // Try native getimagesize() for better performance
        if (null === $this->dimensions) {
            $ext = pathinfo($this->getPath(), PATHINFO_EXTENSION);

            if (in_array($ext, ['gif', 'jpg', 'jpeg', 'png'])) {
                $size = @getimagesize($this->getPath());

                if (!empty($size[0]) && !empty($size[1])) {
                    $this->dimensions = new ImageDimensions(new Box($size[0], $size[1]));
                }
            }
        }

        // Fall back to Imagine
        if (null === $this->dimensions) {
            $this->dimensions = new ImageDimensions($this->imagine->open($this->getPath())->getSize());
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
