<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image;

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
    public function getSize(): PictureConfigurationItemInterface
    {
        if (null === $this->size) {
            $this->setSize(new PictureConfigurationItem());
        }

        return $this->size;
    }

    /**
     * {@inheritdoc}
     */
    public function setSize(PictureConfigurationItemInterface $size): PictureConfigurationInterface
    {
        $this->size = $size;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSizeItems(): array
    {
        return $this->sizeItems;
    }

    /**
     * {@inheritdoc}
     */
    public function setSizeItems(array $sizeItems): PictureConfigurationInterface
    {
        foreach ($sizeItems as $sizeItem) {
            if (!$sizeItem instanceof PictureConfigurationItemInterface) {
                throw new \InvalidArgumentException(
                    '$sizeItems must be an array of PictureConfigurationItemInterface objects'
                );
            }
        }

        $this->sizeItems = $sizeItems;

        return $this;
    }
}
