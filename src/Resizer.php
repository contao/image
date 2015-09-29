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
use Contao\CoreBundle\Adapter\AdapterFactoryInterface;
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
     * @var ConfigAdapter
     */
    private $config;

    /**
     * @var AdapterFactoryInterface
     */
    private $adapterFactory;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Constructor.
     *
     * @param ImagineInterface        $imagine        The imagine object
     * @param Filesystem              $filesystem     The filesystem object
     * @param AdapterFactoryInterface $adapterFactory The adapter factory
     */
    public function __construct(
        ResizeCalculator $calculator,
        ImagineInterface $imagine,
        Filesystem $filesystem,
        AdapterFactoryInterface $adapterFactory
    ) {
        $this->calculator = $calculator;
        $this->imagine = $imagine;
        $this->filesystem = $filesystem;
        $this->adapterFactory = $adapterFactory;
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
        // Pass the `Image` data to the `ResizeCalculator`, resize the image
        // based on the result and return the resized image
    }
}
