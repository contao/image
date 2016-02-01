<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Image;

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
     * @param ImportantPart       $importantPart The important part
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

        $importantPart = $importantPart ?: new ImportantPart(
            new Point(0, 0),
            clone $dimensions->getSize()
        );
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

        if ($mode === 'proportional' && $width && $height) {
            if ($zoomedImportantPart['width'] >= $zoomedImportantPart['height']) {
                $height = null;
            } else {
                $width = null;
            }
        } elseif ($mode === 'box' && $width && $height) {
            if ($zoomedImportantPart['height'] * $width / $zoomedImportantPart['width'] <= $height) {
                $height = null;
            } else {
                $width = null;
            }
        }

        // Crop mode
        if ($width && $height) {

            // Calculate the image part for zoom 0
            $leastZoomed = [
                'x' => 0,
                'y' => 0,
                'width' => $originalWidth,
                'height' => $originalHeight,
            ];

            if ($originalHeight * $width / $originalWidth <= $height) {
                $leastZoomed['width'] = $originalHeight * $width / $height;

                if ($leastZoomed['width'] > $importantPart['width']) {
                    $leastZoomed['x'] = ($originalWidth - $leastZoomed['width']) * $importantPart['x'] / ($originalWidth - $importantPart['width']);
                } else {
                    $leastZoomed['x'] = $importantPart['x'] + (($importantPart['width'] - $leastZoomed['width']) / 2);
                }
            } else {
                $leastZoomed['height'] = $originalWidth * $height / $width;

                if ($leastZoomed['height'] > $importantPart['height']) {
                    $leastZoomed['y'] = ($originalHeight - $leastZoomed['height']) * $importantPart['y'] / ($originalHeight - $importantPart['height']);
                } else {
                    $leastZoomed['y'] = $importantPart['y'] + (($importantPart['height'] - $leastZoomed['height']) / 2);
                }
            }

            // Calculate the image part for zoom 100
            $mostZoomed = $importantPart;

            if ($importantPart['height'] * $width / $importantPart['width'] <= $height) {
                $mostZoomed['height'] = $height * $importantPart['width'] / $width;

                if ($originalHeight > $importantPart['height']) {
                    $mostZoomed['y'] -= ($mostZoomed['height'] - $importantPart['height']) * $importantPart['y'] / ($originalHeight - $importantPart['height']);
                }
            } else {
                $mostZoomed['width'] = $width * $mostZoomed['height'] / $height;

                if ($originalWidth > $importantPart['width']) {
                    $mostZoomed['x'] -= ($mostZoomed['width'] - $importantPart['width']) * $importantPart['x'] / ($originalWidth - $importantPart['width']);
                }
            }

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
}
