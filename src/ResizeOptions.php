<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Resize options used by the Resizer.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class ResizeOptions
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
     * Gets the imagine options.
     *
     * @return array
     */
    public function getImagineOptions()
    {
        return $this->imagineOptions;
    }

    /**
     * Sets the imagine options.
     *
     * @param array $imagineOptions
     *
     * @return self
     */
    public function setImagineOptions(array $imagineOptions)
    {
        $this->imagineOptions = $imagineOptions;

        return $this;
    }

    /**
     * Gets the target path.
     *
     * @return string
     */
    public function getTargetPath()
    {
        return $this->targetPath;
    }

    /**
     * Sets the target path.
     *
     * @param string|null $targetPath
     *
     * @return self
     */
    public function setTargetPath($targetPath)
    {
        if (null !== $targetPath) {

            $targetPath = (string) $targetPath;

            if (!(new Filesystem())->isAbsolutePath($targetPath)) {
                throw new \InvalidArgumentException('"' . $targetPath . '" is not an absolute target path');
            }

        }

        $this->targetPath = $targetPath;

        return $this;
    }

    /**
     * Gets the bypass cache flag.
     *
     * @return string
     */
    public function getBypassCache()
    {
        return $this->bypassCache;
    }

    /**
     * Sets the bypass cache flag.
     *
     * @param bool $bypassCache
     *
     * @return self
     */
    public function setBypassCache($bypassCache)
    {
        $this->bypassCache = (bool) $bypassCache;

        return $this;
    }
}
