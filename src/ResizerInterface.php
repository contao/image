<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Resizer interface.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
interface ResizerInterface
{
    /**
     * Constructor.
     *
     * @param string                         $path
     * @param ResizeCalculatorInterface|null $calculator
     * @param Filesystem|null                $filesystem
     * @param EventDispatcherInterface|null  $eventDispatcher
     */
    public function __construct(
        $path,
        ResizeCalculatorInterface $calculator = null,
        Filesystem $filesystem = null,
        EventDispatcherInterface $eventDispatcher = null
    );

    /**
     * Resizes an Image object.
     *
     * @param ImageInterface               $image
     * @param ResizeConfigurationInterface $config
     * @param ResizeOptionsInterface       $options
     *
     * @return ImageInterface
     */
    public function resize(
        ImageInterface $image,
        ResizeConfigurationInterface $config,
        ResizeOptionsInterface $options
    );
}
