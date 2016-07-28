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
interface PictureConfigurationInterface
{
    /**
     * Gets the size.
     *
     * @return PictureConfigurationItemInterface
     */
    public function getSize();

    /**
     * Sets the size.
     *
     * @param PictureConfigurationItemInterface $size the size
     *
     * @return self
     */
    public function setSize(PictureConfigurationItemInterface $size);

    /**
     * Gets the size items.
     *
     * @return PictureConfigurationItemInterface[]
     */
    public function getSizeItems();

    /**
     * Sets the sizeItems.
     *
     * @param PictureConfigurationItemInterface[] $sizeItems the size items
     *
     * @return self
     */
    public function setSizeItems(array $sizeItems);
}
