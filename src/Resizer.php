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
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Resizes Image objects
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
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * Constructor.
     *
     * @param ResizeCalculator         $calculator The resize calculator object
     * @param ImagineInterface         $imagine    The imagine object
     * @param Filesystem               $filesystem The filesystem object
     * @param string                   $path       The absolute image assets path
     * @param ContaoFrameworkInterface $framework  The Contao framework
     */
    public function __construct(
        ResizeCalculator $calculator,
        ImagineInterface $imagine,
        Filesystem $filesystem,
        $path,
        ContaoFrameworkInterface $framework
    ) {
        $this->calculator = $calculator;
        $this->imagine = $imagine;
        $this->filesystem = $filesystem;
        $this->path = (string) $path;
        $this->framework = $framework;
    }

    /**
     * Resizes an Image object
     *
     * @param Image               $image        The source image
     * @param ResizeConfiguration $resizeConfig The resize configuration
     *
     * @return Image The resized image as new object
     */
    public function resize(Image $image, ResizeConfiguration $resizeConfig)
    {
        $coordinates = $this->calculator->calculate(
            $resizeConfig,
            $image->getDimensions(),
            $image->getImportantPart()
        );

        $targetPath = $this->path . '/' . $this->createTargetPath($image->getPath(), $coordinates);

        if (!$this->filesystem->exists(dirname($targetPath))) {
            $this->filesystem->mkdir(dirname($targetPath));
        }

        $this->imagine
            ->open($image->getPath())
            ->resize($coordinates->getSize())
            ->crop($coordinates->getCropStart(), $coordinates->getCropSize())
            ->save($targetPath);

        return new Image($this->imagine, $this->filesystem, $targetPath);
    }

    /**
     * Creates the target path
     *
     * @param string            $path        The source image path
     * @param ResizeCoordinates $coordinates The resize coordinates
     *
     * @return string The realtive target path
     */
    private function createTargetPath($path, ResizeCoordinates $coordinates)
    {
        $hash = substr(md5(implode('|', [
            $path,
            filemtime($path),
            serialize($coordinates),
        ])), 0, 9);

        $pathinfo = pathinfo($path);

        return substr($hash, 0, 1)
            . '/' . $pathinfo['filename']
            . '-' . substr($hash, 1)
            . '.' . $pathinfo['extension'];
    }
}
