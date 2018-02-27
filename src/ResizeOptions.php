<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image;

use Symfony\Component\Filesystem\Filesystem;

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
