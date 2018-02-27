<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image;

interface PictureConfigurationInterface
{
    /**
     * Returns the size.
     *
     * @return PictureConfigurationItemInterface
     */
    public function getSize();

    /**
     * Sets the size.
     *
     * @param PictureConfigurationItemInterface $size
     *
     * @return self
     */
    public function setSize(PictureConfigurationItemInterface $size);

    /**
     * Returns the size items.
     *
     * @return PictureConfigurationItemInterface[]
     */
    public function getSizeItems();

    /**
     * Sets the size items.
     *
     * @param PictureConfigurationItemInterface[] $sizeItems
     *
     * @return self
     */
    public function setSizeItems(array $sizeItems);
}
