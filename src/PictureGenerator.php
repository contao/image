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

use Contao\ImagineSvg\Imagine as ImagineSvg;

class PictureGenerator implements PictureGeneratorInterface
{
    /**
     * @var ResizerInterface
     */
    private $resizer;

    /**
     * @var ResizeCalculator
     */
    private $calculator;

    /**
     * @var ResizeOptions
     */
    private $resizeOptions;

    public function __construct(ResizerInterface $resizer, ResizeCalculator $calculator = null)
    {
        if (null === $calculator) {
            $calculator = new ResizeCalculator();
        }

        $this->resizer = $resizer;
        $this->calculator = $calculator;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(ImageInterface $image, PictureConfiguration $config, ResizeOptions $options): PictureInterface
    {
        $this->resizeOptions = clone $options;
        $this->resizeOptions->setTargetPath(null);

        $formats = $this->getFormatsFromConfig(
            $config,
            strtolower(pathinfo($image->getPath(), PATHINFO_EXTENSION))
        );

        $sources = [];

        foreach ($config->getSizeItems() as $sizeItem) {
            foreach ($formats as $index => $format) {
                $sources[] = $this->generateSource($image, $sizeItem, $format, $index + 1 === \count($formats));
            }
        }

        $source = $this->generateSource($image, $config->getSize(), array_pop($formats), true);

        foreach ($formats as $format) {
            $sources[] = $this->generateSource($image, $config->getSize(), $format, false);
        }

        return new Picture($source, $sources);
    }

    /**
     * Generates the source.
     */
    private function generateSource(ImageInterface $image, PictureConfigurationItem $config, string $format, bool $lastFormat): array
    {
        $densities = [1];
        $sizesAttribute = $config->getSizes();

        $width1x = $this->calculator
            ->calculate(
                $config->getResizeConfig(),
                new ImageDimensions($image->getDimensions()->getSize(), true),
                $image->getImportantPart()
            )
            ->getCropSize()
            ->getWidth()
        ;

        if (
            $config->getDensities()
            && ($config->getResizeConfig()->getWidth() || $config->getResizeConfig()->getHeight())
        ) {
            if (!$sizesAttribute && false !== strpos($config->getDensities(), 'w')) {
                $sizesAttribute = '100vw';
            }

            if (!$image->getImagine() instanceof ImagineSvg) {
                $densities = $this->parseDensities($config->getDensities(), $width1x);
            }
        }

        $attributes = [];
        $srcset = [];
        $descriptorType = $sizesAttribute ? 'w' : 'x'; // use pixel density descriptors if the sizes attribute is empty

        foreach ($densities as $density) {
            $srcset[] = $this->generateSrcsetItem($image, $config, $density, $descriptorType, $width1x, $format);
        }

        $srcset = $this->removeDuplicateScrsetItems($srcset);

        $attributes['srcset'] = $srcset;
        $attributes['src'] = $srcset[0][0];

        if (!$attributes['src']->getDimensions()->isRelative() && !$attributes['src']->getDimensions()->isUndefined()) {
            $attributes['width'] = $attributes['src']->getDimensions()->getSize()->getWidth();
            $attributes['height'] = $attributes['src']->getDimensions()->getSize()->getHeight();
        }

        if ($sizesAttribute) {
            $attributes['sizes'] = $sizesAttribute;
        }

        if ($config->getMedia()) {
            $attributes['media'] = $config->getMedia();
        }

        if (!$lastFormat) {
            $attributes['type'] = $this->getMimeFromFormat($format);
        }

        return $attributes;
    }

    /**
     * Parse the densities string and return an array of scaling factors.
     *
     * @return array<int,float>
     */
    private function parseDensities(string $densities, int $width1x): array
    {
        $densitiesArray = explode(',', $densities);

        $densitiesArray = array_map(
            static function ($density) use ($width1x) {
                $type = substr(trim($density), -1);

                if ('w' === $type) {
                    return (int) $density / $width1x;
                }

                return (float) $density;
            },
            $densitiesArray
        );

        // Strip empty densities
        $densitiesArray = array_filter($densitiesArray);

        // Add 1x to the beginning of the list
        array_unshift($densitiesArray, 1);

        // Strip duplicates
        return array_values(array_unique($densitiesArray));
    }

    /**
     * Generates a srcset item.
     *
     * @param string $descriptorType x, w or the empty string
     *
     * @return array Array containing an ImageInterface and an optional descriptor string
     */
    private function generateSrcsetItem(ImageInterface $image, PictureConfigurationItem $config, float $density, string $descriptorType, int $width1x, string $format): array
    {
        $resizeConfig = clone $config->getResizeConfig();
        $resizeConfig->setWidth((int) round($resizeConfig->getWidth() * $density));
        $resizeConfig->setHeight((int) round($resizeConfig->getHeight() * $density));

        $options = clone $this->resizeOptions;
        $imagineOptions = $options->getImagineOptions();
        $imagineOptions['format'] = $format;
        $options->setImagineOptions($imagineOptions);

        $resizedImage = $this->resizer->resize($image, $resizeConfig, $options);
        $src = [$resizedImage];

        if ('x' === $descriptorType) {
            $srcX = $resizedImage->getDimensions()->getSize()->getWidth() / $width1x;
            $src[1] = rtrim(rtrim(sprintf('%.3F', $srcX), '0'), '.').'x';
        } elseif ('w' === $descriptorType) {
            $src[1] = $resizedImage->getDimensions()->getSize()->getWidth().'w';
        }

        return $src;
    }

    /**
     * Removes duplicate items from a srcset array.
     *
     * @param array $srcset Array containing an ImageInterface and an optional descriptor string
     */
    private function removeDuplicateScrsetItems(array $srcset): array
    {
        $srcset = array_values(array_filter(
            $srcset,
            static function (array $item) use (&$usedPaths) {
                /** @var array<ImageInterface> $item */
                $key = $item[0]->getPath();

                if (isset($usedPaths[$key])) {
                    return false;
                }

                $usedPaths[$key] = true;

                return true;
            }
        ));

        if (1 === \count($srcset) && isset($srcset[0][1]) && 'x' === substr($srcset[0][1], -1)) {
            unset($srcset[0][1]);
        }

        return $srcset;
    }

    /**
     * @return array<string>
     */
    private function getFormatsFromConfig(PictureConfiguration $config, string $sourceFormat): array
    {
        $formatsConfig = $config->getFormats();

        return array_map(
            static function ($format) use ($config, $sourceFormat) {
                return $format === $config::FORMAT_DEFAULT ? $sourceFormat : $format;
            },
            $formatsConfig[$sourceFormat] ?? $formatsConfig[$config::FORMAT_DEFAULT]
        );
    }

    private function getMimeFromFormat(string $format): string
    {
        static $mapping = [
            'jpg' => 'image/jpeg',
            'wbmp' => 'image/vnd.wap.wbmp',
            'svg' => 'image/svg+xml',
        ];

        return $mapping[$format] ?? 'image/'.$format;
    }
}
