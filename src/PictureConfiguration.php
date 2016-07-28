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
 * Picture resize configuration used by the PictureGenerator.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class PictureConfiguration implements PictureConfigurationInterface
{
    /**
     * @var PictureConfigurationItemInterface
     */
    private $size;

    /**
     * @var PictureConfigurationItemInterface[]
     */
    private $sizeItems = [];

    /**
     * {@inheritdoc}
     */
    public function getSize()
    {
        if (null === $this->size) {
            $this->setSize(new PictureConfigurationItem());
        }

        return $this->size;
    }

    /**
     * {@inheritdoc}
     */
    public function setSize(PictureConfigurationItemInterface $size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSizeItems()
    {
        return $this->sizeItems;
    }

    /**
     * {@inheritdoc}
     */
    public function setSizeItems(array $sizeItems)
    {
        foreach ($sizeItems as $sizeItem) {
            if (!$sizeItem instanceof PictureConfigurationItemInterface) {
                throw new \InvalidArgumentException('$sizeItems must be an array of PictureConfigurationItemInterface objects');
            }
        }

        $this->sizeItems = $sizeItems;

        return $this;
    }
}
