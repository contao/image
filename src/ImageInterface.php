<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image;

use Imagine\Image\ImagineInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Image data.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
interface ImageInterface
{
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
    );

    /**
     * Gets the imagine instance.
     *
     * @return ImagineInterface
     */
    public function getImagine();

    /**
     * Gets the path.
     *
     * @return string
     */
    public function getPath();

    /**
     * Gets the URL relative to the specified root directory.
     *
     * @param string $rootDir
     *
     * @return string
     */
    public function getUrl($rootDir);

    /**
     * Gets the dimensions.
     *
     * @return ImageDimensionsInterface
     */
    public function getDimensions();

    /**
     * Gets the important part.
     *
     * @return ImportantPartInterface
     */
    public function getImportantPart();

    /**
     * Sets the important part.
     *
     * @param ImportantPartInterface $importantPart The important part
     *
     * @return self
     */
    public function setImportantPart(ImportantPartInterface $importantPart = null);
}
