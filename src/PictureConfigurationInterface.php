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

interface PictureConfigurationInterface
{
    public const FORMAT_DEFAULT = '.default';

    /**
     * Returns the size.
     */
    public function getSize(): PictureConfigurationItemInterface;

    /**
     * Sets the size.
     */
    public function setSize(PictureConfigurationItemInterface $size): self;

    /**
     * Returns the size items.
     *
     * @return PictureConfigurationItemInterface[]
     */
    public function getSizeItems(): array;

    /**
     * Sets the size items.
     *
     * @param PictureConfigurationItemInterface[] $sizeItems
     */
    public function setSizeItems(array $sizeItems): self;

    /**
     * Returns the formats.
     *
     * @return array<string,string[]>
     */
    public function getFormats(): array;

    /**
     * Sets the formats.
     *
     * @param array<string,string[]> $formats
     */
    public function setFormats(array $formats): self;
}
