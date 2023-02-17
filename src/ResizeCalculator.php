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
use Imagine\Image\Box;
use Imagine\Image\BoxInterface;
use Imagine\Image\Point;

class ResizeCalculator
{
    public function calculate(ResizeConfiguration $config, ImageDimensions $dimensions, ImportantPart $importantPart = null): ResizeCoordinates
    {
        $zoom = max(0, min(1, $config->getZoomLevel() / 100));
        $importantPartArray = $this->importantPartAsArray($dimensions, $importantPart);

        // If both dimensions are present, use the mode specific calculations
        if ($config->getWidth() && $config->getHeight()) {
            $widthHeight = [$config->getWidth(), $config->getHeight()];

            switch ($config->getMode()) {
                case ResizeConfiguration::MODE_CROP:
                    return $this->calculateCrop($widthHeight, $dimensions, $importantPartArray, $zoom);

                case ResizeConfiguration::MODE_PROPORTIONAL:
                    return $this->calculateProportional($widthHeight, $dimensions, $importantPartArray, $zoom);

                case ResizeConfiguration::MODE_BOX:
                    return $this->calculateBox($widthHeight, $dimensions, $importantPartArray, $zoom);
            }

            throw new InvalidArgumentException(sprintf('Unsupported resize mode "%s"', $config->getMode()));
        }

        // If no dimensions are specified, use the zoomed important part
        if (!$config->getWidth() && !$config->getHeight()) {
            $zoomedImportantPart = $this->zoomImportantPart($importantPartArray, $zoom, $dimensions->getSize());

            return $this->buildCoordinates(
                [$dimensions->getSize()->getWidth(), $dimensions->getSize()->getHeight()],
                [$zoomedImportantPart['x'], $zoomedImportantPart['y']],
                [$zoomedImportantPart['width'], $zoomedImportantPart['height']],
                $dimensions
            );
        }

        // If only one dimension is specified, use the single dimension calculation
        return $this->calculateSingleDimension(
            [$config->getWidth(), $config->getHeight()],
            $dimensions,
            $this->zoomImportantPart($importantPartArray, $zoom, $dimensions->getSize())
        );
    }

    /**
     * Calculates the resize coordinates for mode crop.
     *
     * @param array<int> $size
     */
    private function calculateCrop(array $size, ImageDimensions $original, array $importantPart, float $zoom): ResizeCoordinates
    {
        // Calculate the image part for zoom 0
        $leastZoomed = $this->calculateLeastZoomed(
            $size,
            $original->getSize(),
            $importantPart
        );

        // Calculate the image part for zoom 100
        $mostZoomed = $this->calculateMostZoomed(
            $size,
            $original->getSize(),
            $importantPart
        );

        // If the most zoomed area is larger, no zooming can be applied
        if ($mostZoomed['width'] > $leastZoomed['width']) {
            $mostZoomed = $leastZoomed;
        }

        // Apply zoom
        $zoomedImportantPart = [];

        foreach (['x', 'y', 'width', 'height'] as $key) {
            $zoomedImportantPart[$key] = ($mostZoomed[$key] * $zoom) + ($leastZoomed[$key] * (1 - $zoom));
        }

        $targetX = $zoomedImportantPart['x'] * $size[0] / $zoomedImportantPart['width'];
        $targetY = $zoomedImportantPart['y'] * $size[1] / $zoomedImportantPart['height'];
        $targetWidth = $original->getSize()->getWidth() * $size[0] / $zoomedImportantPart['width'];
        $targetHeight = $original->getSize()->getHeight() * $size[1] / $zoomedImportantPart['height'];

        return $this->buildCoordinates([$targetWidth, $targetHeight], [$targetX, $targetY], $size, $original);
    }

    /**
     * Calculates the resize coordinates for mode proportional.
     *
     * @param array<int> $size
     */
    private function calculateProportional(array $size, ImageDimensions $original, array $importantPart, float $zoom): ResizeCoordinates
    {
        $importantPart = $this->zoomImportantPart($importantPart, $zoom, $original->getSize());

        if ($importantPart['width'] >= $importantPart['height']) {
            $size[1] = 0;
        } else {
            $size[0] = 0;
        }

        return $this->calculateSingleDimension($size, $original, $importantPart);
    }

    /**
     * Calculates the resize coordinates for mode box.
     *
     * @param array<int> $size
     */
    private function calculateBox(array $size, ImageDimensions $original, array $importantPart, float $zoom): ResizeCoordinates
    {
        $importantPart = $this->zoomImportantPart($importantPart, $zoom, $original->getSize());

        if ($importantPart['height'] * $size[0] / $importantPart['width'] <= $size[1]) {
            $size[1] = 0;
        } else {
            $size[0] = 0;
        }

        return $this->calculateSingleDimension($size, $original, $importantPart);
    }

    /**
     * Calculates the resize coordinates for single dimension size.
     *
     * @param array<int> $size
     */
    private function calculateSingleDimension(array $size, ImageDimensions $original, array $importantPart): ResizeCoordinates
    {
        if ($size[0]) {
            // Calculate the height if only the width is given
            $size[1] = max($importantPart['height'] * $size[0] / $importantPart['width'], 1);
        } else {
            // Calculate the width if only the height is given
            $size[0] = max($importantPart['width'] * $size[1] / $importantPart['height'], 1);
        }

        // Apply zoom
        $targetWidth = $original->getSize()->getWidth() / $importantPart['width'] * $size[0];
        $targetHeight = $original->getSize()->getHeight() / $importantPart['height'] * $size[1];
        $targetX = $importantPart['x'] * $targetWidth / $original->getSize()->getWidth();
        $targetY = $importantPart['y'] * $targetHeight / $original->getSize()->getHeight();

        return $this->buildCoordinates([$targetWidth, $targetHeight], [$targetX, $targetY], $size, $original);
    }

    /**
     * Converts an important part to an x, y, width and height array.
     *
     * @return array<string,int>
     */
    private function importantPartAsArray(ImageDimensions $dimensions, ImportantPart $importantPart = null): array
    {
        if (null === $importantPart) {
            $importantPart = new ImportantPart();
        }

        $imageWidth = $dimensions->getSize()->getWidth();
        $imageHeight = $dimensions->getSize()->getHeight();

        $part = [
            'x' => (int) min($imageWidth - 1, round($importantPart->getX() * $imageWidth)),
            'y' => (int) min($imageHeight - 1, round($importantPart->getY() * $imageHeight)),
        ];

        $part['width'] = (int) max(1, min($imageWidth - $part['x'], round($importantPart->getWidth() * $imageWidth)));
        $part['height'] = (int) max(1, min($imageHeight - $part['y'], round($importantPart->getHeight() * $imageHeight)));

        return $part;
    }

    /**
     * Zooms an important part by the specified zoom level.
     *
     * @return array<string,float>
     */
    private function zoomImportantPart(array $importantPart, float $zoom, BoxInterface $origSize): array
    {
        return [
            'x' => $importantPart['x'] * $zoom,
            'y' => $importantPart['y'] * $zoom,
            'width' => $origSize->getWidth()
                - (($origSize->getWidth() - $importantPart['width'] - $importantPart['x']) * $zoom)
                - ($importantPart['x'] * $zoom),
            'height' => $origSize->getHeight()
                - (($origSize->getHeight() - $importantPart['height'] - $importantPart['y']) * $zoom)
                - ($importantPart['y'] * $zoom),
        ];
    }

    /**
     * Builds a resize coordinates object.
     *
     * @param array<int|float> $size
     * @param array<int|float> $cropStart
     * @param array<int|float> $cropSize
     */
    private function buildCoordinates(array $size, array $cropStart, array $cropSize, ImageDimensions $original): ResizeCoordinates
    {
        $scale = 1;

        if (!$original->isRelative() && round($size[0]) > $original->getSize()->getWidth()) {
            $scale = $original->getSize()->getWidth() / $size[0];
        }

        $sizeBox = new Box(
            (int) max(round($size[0] * $scale), 1),
            (int) max(round($size[1] * $scale), 1)
        );

        $cropPoint = new Point(
            (int) min(round($cropStart[0] * $scale), $sizeBox->getWidth() - 1),
            (int) min(round($cropStart[1] * $scale), $sizeBox->getHeight() - 1)
        );

        $cropBox = new Box(
            (int) max(round($cropSize[0] * $scale), 1),
            (int) max(round($cropSize[1] * $scale), 1)
        );

        return new ResizeCoordinates($sizeBox, $cropPoint, $cropBox);
    }

    /**
     * Calculates the least zoomed crop possible.
     *
     * @param array<int>   $size     Target size
     * @param BoxInterface $origSize Original size
     * @param array        $part     Important part
     *
     * @return array<string,float>
     */
    private function calculateLeastZoomed(array $size, BoxInterface $origSize, array $part): array
    {
        $zoomed = [
            'x' => 0,
            'y' => 0,
            'width' => $origSize->getWidth(),
            'height' => $origSize->getHeight(),
        ];

        if ($origSize->getHeight() * $size[0] / $origSize->getWidth() <= $size[1]) {
            $zoomed['width'] = $origSize->getHeight() * $size[0] / $size[1];

            if ($zoomed['width'] > $part['width']) {
                $zoomed['x'] = ($origSize->getWidth() - $zoomed['width'])
                    * $part['x']
                    / ($origSize->getWidth() - $part['width'])
                ;
            } else {
                $zoomed['x'] = $part['x'] + (($part['width'] - $zoomed['width']) / 2);
            }
        } else {
            $zoomed['height'] = $origSize->getWidth() * $size[1] / $size[0];

            if ($zoomed['height'] > $part['height']) {
                $zoomed['y'] = ($origSize->getHeight() - $zoomed['height'])
                    * $part['y']
                    / ($origSize->getHeight() - $part['height'])
                ;
            } else {
                $zoomed['y'] = $part['y'] + (($part['height'] - $zoomed['height']) / 2);
            }
        }

        return $zoomed;
    }

    /**
     * Calculates the most zoomed crop possible.
     *
     * @param array<int>   $size     Target size
     * @param BoxInterface $origSize Original size
     * @param array        $part     Important part
     *
     * @return array<string,float>
     */
    private function calculateMostZoomed(array $size, BoxInterface $origSize, array $part): array
    {
        $zoomed = $part;

        if ($part['height'] * $size[0] / $part['width'] <= $size[1]) {
            $zoomed['height'] = $size[1] * $part['width'] / $size[0];

            if ($origSize->getHeight() > $part['height']) {
                $zoomed['y'] -= ($zoomed['height'] - $part['height'])
                    * $part['y']
                    / ($origSize->getHeight() - $part['height'])
                ;
            }
        } else {
            $zoomed['width'] = $size[0] * $zoomed['height'] / $size[1];

            if ($origSize->getWidth() > $part['width']) {
                $zoomed['x'] -= ($zoomed['width'] - $part['width'])
                    * $part['x']
                    / ($origSize->getWidth() - $part['width'])
                ;
            }
        }

        return $zoomed;
    }
}
