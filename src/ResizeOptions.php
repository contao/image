<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Resize options class.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class ResizeOptions implements ResizeOptionsInterface
{
    /**
     * @var array
     */
    private $imagineOptions = [];

    /**
     * @var string
     */
    private $targetPath;

    /**
     * @var bool
     */
    private $bypassCache = false;

    /**
     * {@inheritdoc}
     */
    public function getImagineOptions()
    {
        return $this->imagineOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function setImagineOptions(array $imagineOptions)
    {
        $this->imagineOptions = $imagineOptions;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargetPath()
    {
        return $this->targetPath;
    }

    /**
     * {@inheritdoc}
     */
    public function setTargetPath($targetPath)
    {
        if (null !== $targetPath) {
            $targetPath = (string) $targetPath;

            if (!(new Filesystem())->isAbsolutePath($targetPath)) {
                throw new \InvalidArgumentException('"'.$targetPath.'" is not an absolute target path');
            }
        }

        $this->targetPath = $targetPath;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getBypassCache()
    {
        return $this->bypassCache;
    }

    /**
     * {@inheritdoc}
     */
    public function setBypassCache($bypassCache)
    {
        $this->bypassCache = (bool) $bypassCache;

        return $this;
    }
}
