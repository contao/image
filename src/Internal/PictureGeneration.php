<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image\Internal;

use Contao\Image\Dimensions;
use Contao\Image\ImageInformation;
use Contao\Image\ImageResizerInterface;
use Contao\Image\ImageResultInterface;
use Contao\Image\ImageSourceInterface;
use Contao\Image\PictureConfiguration;
use Contao\Image\PictureConfigurationItem;
use Contao\Image\ProcessingOptions;
use Contao\Image\ResponsivePicture;
use Contao\Image\ResponsivePictureInterface;
use Contao\Image\TransformationCalculator;

final class PictureGeneration
{
    /**
     * @var ImageResizerInterface
     */
    private $resizer;

    /**
     * @var TransformationCalculator
     */
    private $calculator;

    /**
     * @var ProcessingOptions
     */
    private $resizeOptions;

    private ImageInformation $information;
    private ImageSourceInterface $image;

    public function __construct(ImageResizerInterface $resizer, ImageInformation $information, ImageSourceInterface $image)
    {
        $this->resizer = $resizer;
        $this->calculator = new TransformationCalculator();
        $this->information = $information;
        $this->image = $image;
    }

    public function generate(PictureConfiguration $config, ProcessingOptions $options): ResponsivePictureInterface
    {
        $this->resizeOptions = $options->withDestination(null);

        $formats = $this->getFormatsFromConfig(
            $config,
            $this->information->getFormat()
        );

        $sources = [];

        foreach ($config->getSizeItems() as $sizeItem) {
            foreach ($formats as $index => $format) {
                $sources[] = $this->generateSource($sizeItem, $format, $index + 1 === \count($formats));
            }
        }

        $source = $this->generateSource($config->getSize(), array_pop($formats), true);

        foreach ($formats as $format) {
            $sources[] = $this->generateSource($config->getSize(), $format, false);
        }

        return new ResponsivePicture($source, $sources);
    }

    /**
     * Generates the source.
     */
    private function generateSource(PictureConfigurationItem $config, string $format, bool $lastFormat): array
    {
        $densities = [1];
        $sizesAttribute = $config->getSizes();

        $width1x = $this->nominalWidth($config);

        if (
            $config->getDensities()
            && ($config->getResizeConfig()->getWidth() || $config->getResizeConfig()->getHeight())
        ) {
            if (!$sizesAttribute && false !== strpos($config->getDensities(), 'w')) {
                $sizesAttribute = '100vw';
            }

            if (!\in_array($this->information->getFormat(), ['svg', 'svgz'], true)) {
                $densities = $this->parseDensities($config->getDensities(), $width1x);
            }
        }

        $attributes = [];
        $srcset = [];
        $descriptorType = $sizesAttribute ? 'w' : 'x'; // use pixel density descriptors if the sizes attribute is empty

        foreach ($densities as $density) {
            $srcset[] = $this->generateSrcsetItem($config, $density, new SrcsetContext($descriptorType, $width1x, $format));
        }

        $srcset = $this->removeDuplicateScrsetItems($srcset);

        $attributes = $this->imageAttributes($srcset);

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

    private function nominalWidth(PictureConfigurationItem $config): int
    {
        return $this->calculator
            ->calculate(
                $config->getResizeConfig(),
                new Dimensions($this->information->getDimensions()->getSize(), Dimensions::RELATIVE),
                $this->image->getImportantPart()
            )
            ->getCropSize()
            ->getWidth()
        ;
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
     * The context carries the x, w or empty descriptor type.
     *
     * @return array Array containing an ImageResultInterface and an optional descriptor string
     */
    private function generateSrcsetItem(PictureConfigurationItem $config, float $density, SrcsetContext $context): array
    {
        $resizeConfig = clone $config->getResizeConfig();
        $resizeConfig = $resizeConfig->withWidth((int) round($resizeConfig->getWidth() * $density));
        $resizeConfig = $resizeConfig->withHeight((int) round($resizeConfig->getHeight() * $density));

        $options = $this->resizeOptions->withEncoding($this->resizeOptions->getEncoding()->withFormat($context->format));
        $resizedImage = $this->resizer->resize($this->image, $resizeConfig, $options);
        $src = [$resizedImage];

        if ('x' === $context->descriptorType) {
            $srcX = $resizedImage->getDimensions()->getSize()->getWidth() / $context->width;
            $src[1] = rtrim(rtrim(sprintf('%.3F', $srcX), '0'), '.').'x';
        } elseif ('w' === $context->descriptorType) {
            $src[1] = $resizedImage->getDimensions()->getSize()->getWidth().'w';
        }

        return $src;
    }

    /**
     * Removes duplicate items from a srcset array.
     *
     * @param array $srcset Array containing an ImageResultInterface and an optional descriptor string
     */
    private function removeDuplicateScrsetItems(array $srcset): array
    {
        $srcset = array_values(array_filter(
            $srcset,
            static function (array $item) use (&$usedPaths) {
                /** @var array<ImageResultInterface> $item */
                $key = $item[0]->getIdentity()->getPath();

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
            'svgz' => 'image/svg+xml',
        ];

        return $mapping[$format] ?? 'image/'.$format;
    }

    private function imageAttributes(array $srcset): array
    {
        $attributes = [];
        $attributes['srcset'] = $srcset;
        $attributes['src'] = $srcset[0][0];

        if (!$attributes['src']->getDimensions()->isRelative() && !$attributes['src']->getDimensions()->isUndefined()) {
            $attributes['width'] = $attributes['src']->getDimensions()->getSize()->getWidth();
            $attributes['height'] = $attributes['src']->getDimensions()->getSize()->getHeight();
        }

        return $attributes;
    }
}
