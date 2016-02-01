<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Image;

/**
 * Picture resize configuration used by the PictureGenerator.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class PictureConfiguration
{
    /**
     * @var PictureConfigurationItem
     */
    private $size;

    /**
     * @var PictureConfigurationItem[]
     */
    private $sizeItems = [];

    /**
     * Gets the size.
     *
     * @return PictureConfigurationItem
     */
    public function getSize()
    {
        if (null === $this->size) {
            $this->setSize(new PictureConfigurationItem());
        }

        return $this->size;
    }

    /**
     * Sets the size.
     *
     * @param PictureConfigurationItem $size the size
     *
     * @return self
     */
    public function setSize(PictureConfigurationItem $size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Gets the size items.
     *
     * @return PictureConfigurationItem[]
     */
    public function getSizeItems()
    {
        return $this->sizeItems;
    }

    /**
     * Sets the sizeItems.
     *
     * @param PictureConfigurationItem[] $sizeItems the size items
     *
     * @return self
     */
    public function setSizeItems(array $sizeItems)
    {
        foreach ($sizeItems as $sizeItem) {
            if (!$sizeItem instanceof PictureConfigurationItem) {
                throw new \InvalidArgumentException('$sizeItems must be an array of PictureConfigurationItem objects');
            }
        }

        $this->sizeItems = $sizeItems;

        return $this;
    }
}
