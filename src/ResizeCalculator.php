<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image;

use Imagine\Image\Box;
use Imagine\Image\Point;

/**
 * Calculates image coordinates for resizing Image objects.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class ResizeCalculator
{
    /**
     * Resizes an Image object.
     *
     * @param ResizeConfiguration $config        The resize configuration
     * @param ImageDimensions     $dimensions    The image dimensions
     * @param ImportantPart|null  $importantPart The important part
     *
     * @return ResizeCoordinates The resize coordinates
     */
    public function calculate(
        ResizeConfiguration $config,
        ImageDimensions $dimensions,
        ImportantPart $importantPart = null
    ) {
        $width = $config->getWidth();
        $height = $config->getHeight();
        $originalWidth = $dimensions->getSize()->getWidth();
        $originalHeight = $dimensions->getSize()->getHeight();
        $mode = $config->getMode();
        $zoom = max(0, min(1, (int) $config->getZoomLevel() / 100));

        if (null === $importantPart) {
            $importantPart = new ImportantPart(
                new Point(0, 0),
                clone $dimensions->getSize()
            );
        }
        $importantPart = [
            'x' => $importantPart->getPosition()->getX(),
            'y' => $importantPart->getPosition()->getY(),
            'width' => $importantPart->getSize()->getWidth(),
            'height' => $importantPart->getSize()->getHeight(),
        ];

        $zoomedImportantPart = [
            'x' => $importantPart['x'] * $zoom,
            'y' => $importantPart['y'] * $zoom,
            'width' => $originalWidth - (($originalWidth - $importantPart['width'] - $importantPart['x']) * $zoom) - ($importantPart['x'] * $zoom),
            'height' => $originalHeight - (($originalHeight - $importantPart['height'] - $importantPart['y']) * $zoom) - ($importantPart['y'] * $zoom),
        ];

        // If no dimensions are specified, use the zoomed original width
        if (!$width && !$height) {
            $width = $zoomedImportantPart['width'];
        }

        if ($width && $height) {
            if ($mode === ResizeConfiguration::MODE_PROPORTIONAL) {
                if ($zoomedImportantPart['width'] >= $zoomedImportantPart['height']) {
                    $height = 0;
                } else {
                    $width = 0;
                }
            } elseif ($mode === ResizeConfiguration::MODE_BOX) {
                if ($zoomedImportantPart['height'] * $width / $zoomedImportantPart['width'] <= $height) {
                    $height = 0;
                } else {
                    $width = 0;
                }
            }
        }

        // Crop mode
        if ($width && $height) {

            // Calculate the image part for zoom 0
            $leastZoomed = $this->calculateLeastZoomed(
                [$width, $height],
                [$originalWidth, $originalHeight],
                $importantPart
            );

            // Calculate the image part for zoom 100
            $mostZoomed = $this->calculateMostZoomed(
                [$width, $height],
                [$originalWidth, $originalHeight],
                $importantPart
            );

            // If the most zoomed area is larger, no zooming can be applied
            if ($mostZoomed['width'] > $leastZoomed['width']) {
                $mostZoomed = $leastZoomed;
            }

            // Apply zoom
            foreach (['x', 'y', 'width', 'height'] as $key) {
                $zoomedImportantPart[$key] = ($mostZoomed[$key] * $zoom) + ($leastZoomed[$key] * (1 - $zoom));
            }

            $targetX = $zoomedImportantPart['x'] * $width / $zoomedImportantPart['width'];
            $targetY = $zoomedImportantPart['y'] * $height / $zoomedImportantPart['height'];
            $targetWidth = $originalWidth * $width / $zoomedImportantPart['width'];
            $targetHeight = $originalHeight * $height / $zoomedImportantPart['height'];
        } else {

            // Calculate the height if only the width is given
            if ($width) {
                $height = max($zoomedImportantPart['height'] * $width / $zoomedImportantPart['width'], 1);
            }

            // Calculate the width if only the height is given
            elseif ($height) {
                $width = max($zoomedImportantPart['width'] * $height / $zoomedImportantPart['height'], 1);
            }

            // Apply zoom
            $targetWidth = $originalWidth / $zoomedImportantPart['width'] * $width;
            $targetHeight = $originalHeight / $zoomedImportantPart['height'] * $height;
            $targetX = $zoomedImportantPart['x'] * $targetWidth / $originalWidth;
            $targetY = $zoomedImportantPart['y'] * $targetHeight / $originalHeight;
        }

        return new ResizeCoordinates(
            new Box(
                (int) round($targetWidth),
                (int) round($targetHeight)
            ),
            new Point(
                (int) round($targetX),
                (int) round($targetY)
            ),
            new Box(
                (int) round($width),
                (int) round($height)
            )
        );
    }

    /**
     * Calculate the least zoomed crop possible.
     *
     * @param array $size Target size
     * @param array $orig Original size
     * @param array $part Important part
     *
     * @return array
     */
    private function calculateLeastZoomed($size, $orig, $part)
    {
        $zoomed = [
            'x' => 0,
            'y' => 0,
            'width' => $orig[0],
            'height' => $orig[1],
        ];

        if ($orig[1] * $size[0] / $orig[0] <= $size[1]) {
            $zoomed['width'] = $orig[1] * $size[0] / $size[1];

            if ($zoomed['width'] > $part['width']) {
                $zoomed['x'] = ($orig[0] - $zoomed['width']) * $part['x'] / ($orig[0] - $part['width']);
            } else {
                $zoomed['x'] = $part['x'] + (($part['width'] - $zoomed['width']) / 2);
            }
        } else {
            $zoomed['height'] = $orig[0] * $size[1] / $size[0];

            if ($zoomed['height'] > $part['height']) {
                $zoomed['y'] = ($orig[1] - $zoomed['height']) * $part['y'] / ($orig[1] - $part['height']);
            } else {
                $zoomed['y'] = $part['y'] + (($part['height'] - $zoomed['height']) / 2);
            }
        }

        return $zoomed;
    }

    /**
     * Calculate the most zoomed crop possible.
     *
     * @param array $size Target size
     * @param array $orig Original size
     * @param array $part Important part
     *
     * @return array
     */
    private function calculateMostZoomed($size, $orig, $part)
    {
        $zoomed = $part;

        if ($part['height'] * $size[0] / $part['width'] <= $size[1]) {
            $zoomed['height'] = $size[1] * $part['width'] / $size[0];

            if ($orig[1] > $part['height']) {
                $zoomed['y'] -= ($zoomed['height'] - $part['height']) * $part['y'] / ($orig[1] - $part['height']);
            }
        } else {
            $zoomed['width'] = $size[0] * $zoomed['height'] / $size[1];

            if ($orig[0] > $part['width']) {
                $zoomed['x'] -= ($zoomed['width'] - $part['width']) * $part['x'] / ($orig[0] - $part['width']);
            }
        }

        return $zoomed;
    }
}
