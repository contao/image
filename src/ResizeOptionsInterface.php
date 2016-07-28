<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image;

/**
 * Resize options interface.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
interface ResizeOptionsInterface
{
    /**
     * Returns the imagine options.
     *
     * @return array
     */
    public function getImagineOptions();

    /**
     * Returns the imagine options.
     *
     * @param array $imagineOptions
     *
     * @return self
     */
    public function setImagineOptions(array $imagineOptions);

    /**
     * Returns the target path.
     *
     * @return string
     */
    public function getTargetPath();

    /**
     * Sets the target path.
     *
     * @param string|null $targetPath
     *
     * @return self
     */
    public function setTargetPath($targetPath);

    /**
     * Returns the bypass cache flag.
     *
     * @return string
     */
    public function getBypassCache();

    /**
     * Sets the bypass cache flag.
     *
     * @param bool $bypassCache
     *
     * @return self
     */
    public function setBypassCache($bypassCache);
}
