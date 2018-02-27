<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image;

use Imagine\Image\Box;
use Imagine\Image\BoxInterface;
use Imagine\Image\Point;

class ResizeCalculator implements ResizeCalculatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function calculate(ResizeConfigurationInterface $config, ImageDimensionsInterface $dimensions, ImportantPartInterface $importantPart = null)
    {
        $zoom = max(0, min(1, (int) $config->getZoomLevel() / 100));
        $importantPart = $this->importantPartAsArray($dimensions, $importantPart);

        // If both dimensions are present, use the mode specific calculations
        if ($config->getWidth() && $config->getHeight()) {
            $widthHeight = [$config->getWidth(), $config->getHeight()];

            switch ($config->getMode()) {
                case ResizeConfigurationInterface::MODE_CROP:
                    return $this->calculateCrop($widthHeight, $dimensions, $importantPart, $zoom);

                case ResizeConfigurationInterface::MODE_PROPORTIONAL:
                    return $this->calculateProportional($widthHeight, $dimensions, $importantPart, $zoom);

                case ResizeConfigurationInterface::MODE_BOX:
                    return $this->calculateBox($widthHeight, $dimensions, $importantPart, $zoom);
            }

            throw new \InvalidArgumentException(sprintf('Unsupported resize mode "%s"', $config->getMode()));
        }

        // If no dimensions are specified, use the zoomed important part
        if (!$config->getWidth() && !$config->getHeight()) {
            $zoomedImportantPart = $this->zoomImportantPart($importantPart, $zoom, $dimensions->getSize());

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
            $this->zoomImportantPart($importantPart, $zoom, $dimensions->getSize())
        );
    }

    /**
     * Calculates resize coordinates for mode crop.
     *
     * @param int[]                    $size
     * @param ImageDimensionsInterface $original
     * @param array                    $importantPart
     * @param float                    $zoom
     *
     * @return ResizeCoordinates
     */
    private function calculateCrop($size, $original, $importantPart, $zoom)
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
     * Calculates resize coordinates for mode proportional.
     *
     * @param int[]                    $size
     * @param ImageDimensionsInterface $original
     * @param array                    $importantPart
     * @param float                    $zoom
     *
     * @return ResizeCoordinates
     */
    private function calculateProportional($size, $original, $importantPart, $zoom)
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
     * Calculates resize coordinates for mode box.
     *
     * @param int[]                    $size
     * @param ImageDimensionsInterface $original
     * @param array                    $importantPart
     * @param float                    $zoom
     *
     * @return ResizeCoordinates
     */
    private function calculateBox($size, $original, $importantPart, $zoom)
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
     * Calculates resize coordinates for single dimension size.
     *
     * @param int[]                    $size
     * @param ImageDimensionsInterface $original
     * @param array                    $importantPart
     *
     * @return ResizeCoordinates
     */
    private function calculateSingleDimension($size, $original, $importantPart)
    {
        // Calculate the height if only the width is given
        if ($size[0]) {
            $size[1] = max($importantPart['height'] * $size[0] / $importantPart['width'], 1);
        }

        // Calculate the width if only the height is given
        else {
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
     * @param ImageDimensionsInterface    $dimensions
     * @param ImportantPartInterface|null $importantPart
     *
     * @return array<string,int>
     */
    private function importantPartAsArray(ImageDimensionsInterface $dimensions, ImportantPartInterface $importantPart = null)
    {
        if (null === $importantPart) {
            $importantPart = new ImportantPart(new Point(0, 0), clone $dimensions->getSize());
        }

        return [
            'x' => $importantPart->getPosition()->getX(),
            'y' => $importantPart->getPosition()->getY(),
            'width' => $importantPart->getSize()->getWidth(),
            'height' => $importantPart->getSize()->getHeight(),
        ];
    }

    /**
     * Zooms an important part by the specified zoom level.
     *
     * @param array        $importantPart
     * @param float        $zoom
     * @param BoxInterface $origSize
     *
     * @return array<string,float>
     */
    private function zoomImportantPart(array $importantPart, $zoom, BoxInterface $origSize)
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
     * @param array                    $size
     * @param array                    $cropStart
     * @param array                    $cropSize
     * @param ImageDimensionsInterface $original
     *
     * @return ResizeCoordinates
     */
    private function buildCoordinates($size, $cropStart, $cropSize, $original)
    {
        $scale = 1;

        if (!$original->isRelative() && round($size[0]) > $original->getSize()->getWidth()) {
            $scale = $original->getSize()->getWidth() / $size[0];
        }

        return new ResizeCoordinates(
            new Box(
                (int) round($size[0] * $scale),
                (int) round($size[1] * $scale)
            ),
            new Point(
                (int) round($cropStart[0] * $scale),
                (int) round($cropStart[1] * $scale)
            ),
            new Box(
                (int) round($cropSize[0] * $scale),
                (int) round($cropSize[1] * $scale)
            )
        );
    }

    /**
     * Calculates the least zoomed crop possible.
     *
     * @param array        $size     Target size
     * @param BoxInterface $origSize Original size
     * @param array        $part     Important part
     *
     * @return array<string,float>
     */
    private function calculateLeastZoomed($size, $origSize, $part)
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
     * @param array        $size     Target size
     * @param BoxInterface $origSize Original size
     * @param array        $part     Important part
     *
     * @return array<string,float>
     */
    private function calculateMostZoomed($size, $origSize, $part)
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
