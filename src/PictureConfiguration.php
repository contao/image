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

use Contao\Image\Exception\InvalidArgumentException;

class PictureConfiguration
{
    public const FORMAT_DEFAULT = '.default';

    /**
     * @var PictureConfigurationItem
     */
    private $size;

    /**
     * @var array<PictureConfigurationItem>
     */
    private $sizeItems = [];

    /**
     * @var array<array>
     */
    private $formats = [];

    public function getSize(): PictureConfigurationItem
    {
        if (null === $this->size) {
            $this->setSize(new PictureConfigurationItem());
        }

        return $this->size;
    }

    public function setSize(PictureConfigurationItem $size): self
    {
        $this->size = $size;

        return $this;
    }

    /**
     * @return array<PictureConfigurationItem>
     */
    public function getSizeItems(): array
    {
        return $this->sizeItems;
    }

    /**
     * @param array<PictureConfigurationItem> $sizeItems
     */
    public function setSizeItems(array $sizeItems): self
    {
        foreach ($sizeItems as $sizeItem) {
            if (!$sizeItem instanceof PictureConfigurationItem) {
                throw new InvalidArgumentException('$sizeItems must be an array of PictureConfigurationItem objects');
            }
        }

        $this->sizeItems = $sizeItems;

        return $this;
    }

    /**
     * @return array<string, array<string>>
     */
    public function getFormats(): array
    {
        return $this->formats ?: [self::FORMAT_DEFAULT => [self::FORMAT_DEFAULT]];
    }

    /**
     * @param array<string, array<string>> $formats
     */
    public function setFormats(array $formats): self
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

        throw new InvalidArgumentException(sprintf('Invalid image format "%s".', $format));
    }
}
