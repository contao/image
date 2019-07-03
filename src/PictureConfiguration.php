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
     * @var array[]
     */
    private $formats = [];

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

    /**
     * {@inheritdoc}
     */
    public function getFormats(): array
    {
        return $this->formats ?: [self::FORMAT_DEFAULT => [self::FORMAT_DEFAULT]];
    }

    /**
     * {@inheritdoc}
     */
    public function setFormats(array $formats): PictureConfigurationInterface
    {
        if (!isset($formats[self::FORMAT_DEFAULT])) {
            $formats[self::FORMAT_DEFAULT] = [self::FORMAT_DEFAULT];
        }

        foreach ($formats as $sourceFormat => $targetFormats) {
            $this->assertValidFormat($sourceFormat);
            foreach ($targetFormats as $targetFormat) {
                $this->assertValidFormat($targetFormat);
            }
        }

        $this->formats = $formats;

        return $this;
    }

    /**
     * Throws an exception on invalid image formats.
     */
    private function assertValidFormat(string $format): void
    {
        if (self::FORMAT_DEFAULT === $format) {
            return;
        }

        if (preg_match('/^[a-z0-9]+$/', $format)) {
            return;
        }

        throw new \InvalidArgumentException(sprintf('Invalid image format "%s".', $format));
    }
}
